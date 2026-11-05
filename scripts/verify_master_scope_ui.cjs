const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ARTIFACTS_DIR = 'C:\\Users\\Ashutosh Pandey\\.gemini\\antigravity\\brain\\25b4cf0e-c22f-4d11-9d00-f1055a2b7e63';

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function getWsUrl() {
    for (let i = 0; i < 30; i++) {
        try {
            const res = await fetch('http://127.0.0.1:9222/json/version');
            if (res.ok) {
                const data = await res.json();
                return data.webSocketDebuggerUrl;
            }
        } catch (e) {
            // waiting for Chrome
        }
        await sleep(500);
    }
    throw new Error('Could not connect to Chrome CDP endpoint after 15 seconds.');
}

class CdpClient {
    constructor(wsUrl) {
        this.wsUrl = wsUrl;
        this.ws = null;
        this.nextId = 1;
        this.callbacks = new Map();
    }

    async connect() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(this.wsUrl);
            this.ws.onopen = () => resolve();
            this.ws.onerror = (err) => reject(err);
            this.ws.onmessage = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id && this.callbacks.has(msg.id)) {
                    const cb = this.callbacks.get(msg.id);
                    this.callbacks.delete(msg.id);
                    if (msg.error) {
                        cb.reject(new Error(msg.error.message));
                    } else {
                        cb.resolve(msg.result);
                    }
                }
            };
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = this.nextId++;
            this.callbacks.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    async evaluate(expression) {
        const res = await this.send('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true,
        });
        return res.result ? res.result.value : null;
    }

    async navigate(url) {
        await this.send('Page.navigate', { url });
        await sleep(2000);
    }

    async captureScreenshot(filename) {
        const res = await this.send('Page.captureScreenshot', { format: 'png' });
        const filePath = path.join(ARTIFACTS_DIR, filename);
        fs.writeFileSync(filePath, Buffer.from(res.data, 'base64'));
        console.log(`[Screenshot Saved] -> ${filePath}`);
        return filePath;
    }
}

async function run() {
    const chromeProfileDir = path.join(process.env.TEMP, 'chrome_scope_runner_' + Date.now());
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--remote-debugging-address=127.0.0.1',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--window-size=1600,1050',
        '--user-data-dir=' + chromeProfileDir,
        'about:blank'
    ]);

    chrome.on('error', (err) => console.error('[Chrome Spawn Error]', err));

    try {
        const browserWsUrl = await getWsUrl();
        const newTargetRes = await fetch('http://127.0.0.1:9222/json/new', { method: 'PUT' });
        const targetData = await newTargetRes.json();
        const pageWsUrl = targetData.webSocketDebuggerUrl;

        const client = new CdpClient(pageWsUrl);
        await client.connect();

        await client.send('Page.enable');
        await client.send('Runtime.enable');
        await client.send('DOM.enable');

        await client.send('Emulation.setDeviceMetricsOverride', {
            width: 1600,
            height: 1050,
            deviceScaleFactor: 1,
            mobile: false
        });

        // 1. Authenticate as HO Admin
        console.log('[Step 1] Logging in as HO Admin...');
        await client.navigate('http://127.0.0.1:8000/login');
        await client.evaluate(`
            document.querySelector('input[name="email"]').value = 'ho_admin@enterprise.com';
            document.querySelector('input[name="password"]').value = 'password';
            document.querySelector('form').submit();
        `);
        await sleep(2500);

        // 2. Navigate to Admin Masters
        console.log('[Step 2] Navigating to /admin/masters...');
        await client.navigate('http://127.0.0.1:8000/admin/masters');
        await sleep(1500);

        // Capture table with Scope column badges
        await client.captureScreenshot('39_master_table_scope_badges.png');

        // 3. Open Add Master Value Modal
        console.log('[Step 3] Opening Add Master Value modal...');
        await client.evaluate(`
            const modalEl = document.getElementById('createMasterModal');
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        `);
        await sleep(1000);

        // Capture default modal state (Active Company selected, Help text visible)
        await client.captureScreenshot('40_add_master_modal_scope_default.png');

        // 4. Select "Selected Branch" Scope, pick Company, verify Branch dynamically populates
        console.log('[Step 4] Switching Scope to "Selected Branch"...');
        await client.evaluate(`
            const scopeSelect = document.getElementById('createMasterScope');
            scopeSelect.value = 'selected_branch';
            scopeSelect.dispatchEvent(new Event('change'));

            const compSelect = document.getElementById('createMasterCompany');
            // Select second option (a company)
            if (compSelect.options.length > 1) {
                compSelect.selectedIndex = 1;
                compSelect.dispatchEvent(new Event('change'));
            }
        `);
        await sleep(800);

        // Capture dynamic Selected Branch view
        await client.captureScreenshot('41_add_master_modal_selected_branch_dynamic_fields.png');

        // 5. Hide create modal and open Edit modal on first edit button
        console.log('[Step 5] Opening Edit Master modal...');
        await client.evaluate(`
            const createModal = bootstrap.Modal.getInstance(document.getElementById('createMasterModal'));
            if (createModal) createModal.hide();
        `);
        await sleep(800);

        await client.evaluate(`
            const firstEditBtn = document.querySelector('.btn-edit-master');
            if (firstEditBtn) {
                firstEditBtn.click();
            }
        `);
        await sleep(1000);

        // Capture Edit modal
        await client.captureScreenshot('42_edit_master_modal_scope_populated.png');

        console.log('Visual verification complete!');
    } catch (e) {
        console.error('Error during execution:', e);
    } finally {
        try {
            chrome.kill();
        } catch (e) {}
    }
}

run();

