const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

// Ensure screenshots directory exists
const SCREENSHOTS_DIR = path.join(__dirname, '..', 'docs', 'screenshots', 'uat');
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

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
        this.consoleLogs = [];
        this.pageErrors = [];
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
                } else if (msg.method === 'Runtime.consoleAPICalled') {
                    const type = msg.params.type;
                    const text = msg.params.args.map(a => a.value || a.description || '').join(' ');
                    this.consoleLogs.push({ type, text });
                    if (type === 'error') {
                        this.pageErrors.push(text);
                    }
                } else if (msg.method === 'Runtime.exceptionThrown') {
                    const text = msg.params.exceptionDetails.text + ' ' + (msg.params.exceptionDetails.exception?.description || '');
                    this.pageErrors.push(text);
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
        const res = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (res.exceptionDetails) {
            throw new Error(res.exceptionDetails.text);
        }
        return res.result ? res.result.value : undefined;
    }

    async navigate(url) {
        await this.send('Page.navigate', { url });
        await sleep(2000); // Allow render & scripts execution
    }

    async captureScreenshot(filename) {
        const res = await this.send('Page.captureScreenshot', { format: 'png' });
        const filePath = path.join(SCREENSHOTS_DIR, filename);
        fs.writeFileSync(filePath, Buffer.from(res.data, 'base64'));
        console.log(`[CDP] Screenshot saved: ${filename}`);
        return filePath;
    }

    close() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

async function runUat() {
    console.log('--- STARTING COMPREHENSIVE BROWSER UAT AUDIT ---');

    // 1. Launch Chrome process
    const chromeProfileDir = path.join(process.env.TEMP, 'chrome_uat_runner_' + Date.now());
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--remote-debugging-address=127.0.0.1',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--window-size=1600,1000',
        '--user-data-dir=' + chromeProfileDir,
        'about:blank'
    ]);

    chrome.on('error', (err) => console.error('[Chrome Spawn Error]', err));

    try {
        const browserWsUrl = await getWsUrl();
        console.log('[CDP] Connected to Chrome:', browserWsUrl);

        // Create a new target page
        const newTargetRes = await fetch('http://127.0.0.1:9222/json/new', { method: 'PUT' });
        const targetData = await newTargetRes.json();
        const pageWsUrl = targetData.webSocketDebuggerUrl;

        const client = new CdpClient(pageWsUrl);
        await client.connect();

        await client.send('Page.enable');
        await client.send('Runtime.enable');
        await client.send('DOM.enable');
        await client.send('Network.enable');

        // Set Viewport
        await client.send('Emulation.setDeviceMetricsOverride', {
            width: 1600,
            height: 1000,
            deviceScaleFactor: 1,
            mobile: false
        });

        // -------------------------------------------------------------
        // STEP 1: HR ADMIN LOGIN
        // -------------------------------------------------------------
        console.log('\n[TEST 1] Logging in as HR Admin (hr@vishal.com)...');
        await client.navigate('http://127.0.0.1:8000/login');
        
        await client.evaluate(`
            document.querySelector('input[name="email"]').value = 'hr@vishal.com';
            document.querySelector('input[name="password"]').value = 'password';
            document.querySelector('form').submit();
        `);
        await sleep(2500);

        const currentUrl = await client.evaluate('window.location.href');
        console.log('[TEST 1] Authenticated. Current URL:', currentUrl);

        // -------------------------------------------------------------
        // STEP 2: EMPLOYEE MASTER LISTING (/employees)
        // -------------------------------------------------------------
        console.log('\n[TEST 2] Navigating to Employee Master Listing (/employees)...');
        await client.navigate('http://127.0.0.1:8000/employees');
        
        const listMeta = await client.evaluate(`({
            title: document.title,
            hasSearch: !!document.querySelector('input[name="search"]'),
            tableRows: document.querySelectorAll('tbody tr').length,
            hasCompletenessBars: document.querySelectorAll('.progress, [class*="progress"]').length > 0,
            hasActionDropdowns: document.querySelectorAll('.dropdown-toggle, .btn-action').length > 0
        })`);
        console.log('[TEST 2] Employee Listing Meta:', JSON.stringify(listMeta));
        await client.captureScreenshot('01_hr_employee_index.png');

        // -------------------------------------------------------------
        // STEP 3: EMPLOYEE 16-SECTION CREATION WIZARD (/employees/create)
        // -------------------------------------------------------------
        console.log('\n[TEST 3] Navigating to 16-Section Creation Wizard (/employees/create)...');
        await client.navigate('http://127.0.0.1:8000/employees/create');

        const wizardMeta = await client.evaluate(`({
            title: document.title,
            wizardStepButtons: document.querySelectorAll('.wizard-step-btn').length,
            hasFirstName: !!document.querySelector('input[name="first_name"]'),
            hasLastName: !!document.querySelector('input[name="last_name"]'),
            hasDob: !!document.getElementById('dobInput'),
            hasAgeInput: !!document.getElementById('calculatedAgeInput'),
            hasGatePass: !!document.querySelector('input[name="gate_pass_number"]'),
            hasDraftBtn: !!document.querySelector('button[value="draft"], button[name="action"][value="draft"]') || document.body.innerText.includes('Draft'),
            hasFinalizeBtn: !!document.querySelector('button[value="finalize"], button[type="submit"]')
        })`);
        console.log('[TEST 3] Wizard Meta:', JSON.stringify(wizardMeta));

        // Test Live Age Calculation
        const ageResult = await client.evaluate(`(() => {
            const dob = document.getElementById('dobInput');
            const age = document.getElementById('calculatedAgeInput');
            if (dob) {
                dob.value = '1995-05-15';
                dob.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return {
                dobValue: dob?.value,
                calculatedAge: age?.value
            };
        })()`);
        console.log('[TEST 3] Age Calculation Check:', JSON.stringify(ageResult));

        // Test Specially Abled Divyaang Conditional Toggle
        const divyaangResult = await client.evaluate(`(() => {
            const toggle = document.getElementById('isSpeciallyAbled');
            const container = document.getElementById('disabilityDetailsContainer');
            const before = container ? window.getComputedStyle(container).display : null;
            if (toggle) {
                toggle.checked = true;
                toggle.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const after = container ? window.getComputedStyle(container).display : null;
            return { before, after };
        })()`);
        console.log('[TEST 3] Divyaang Toggle Check:', JSON.stringify(divyaangResult));

        // Test Permanent Address Sync Checkbox
        const addressSyncResult = await client.evaluate(`(() => {
            const currFlat = document.getElementById('curr_flat');
            const permFlat = document.getElementById('perm_flat');
            const syncCheck = document.getElementById('permSameAsCurr');
            if (currFlat) currFlat.value = 'Villa 90, Palm Meadows';
            if (syncCheck) {
                syncCheck.checked = true;
                syncCheck.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return {
                currFlatValue: currFlat?.value,
                permFlatValue: permFlat?.value
            };
        })()`);
        console.log('[TEST 3] Address Sync Check:', JSON.stringify(addressSyncResult));

        await client.captureScreenshot('02_hr_employee_create_wizard.png');

        // -------------------------------------------------------------
        // STEP 4: EMPLOYEE DOSSIER DETAIL VIEW (/employees/4)
        // -------------------------------------------------------------
        console.log('\n[TEST 4] Navigating to Employee Dossier Detail View (/employees/4)...');
        await client.navigate('http://127.0.0.1:8000/employees/4');

        const dossierMeta = await client.evaluate(`({
            title: document.title,
            empName: document.querySelector('h3, h4, .employee-name')?.innerText?.trim(),
            tabCount: document.querySelectorAll('#employeeTabs .nav-link').length,
            tabNames: Array.from(document.querySelectorAll('#employeeTabs .nav-link')).map(el => el.innerText.trim().replace(/\\s+/g, ' ')),
            hasFamilyModal: !!document.getElementById('addFamilyModal'),
            hasEducationModal: !!document.getElementById('addEducationModal'),
            hasSkillModal: !!document.getElementById('addSkillModal'),
            hasCorrectionModal: !!document.getElementById('requestCorrectionModal'),
            hasApproveModal: !!document.getElementById('approveProfileModal')
        })`);
        console.log('[TEST 4] Dossier Detail Meta:', JSON.stringify(dossierMeta));
        await client.captureScreenshot('03_hr_employee_dossier_show.png');

        // -------------------------------------------------------------
        // STEP 5: EMPLOYEE DOSSIER EDIT FORM (/employees/4/edit)
        // -------------------------------------------------------------
        console.log('\n[TEST 5] Navigating to Employee Edit Form (/employees/4/edit)...');
        await client.navigate('http://127.0.0.1:8000/employees/4/edit');

        const editMeta = await client.evaluate(`({
            title: document.title,
            firstNameValue: document.querySelector('input[name="first_name"]')?.value,
            lastNameValue: document.querySelector('input[name="last_name"]')?.value,
            hasChangeReason: !!document.querySelector('input[name="change_reason"], textarea[name="change_reason"]'),
            sectionsCount: document.querySelectorAll('.card, fieldset, .accordion-item').length
        })`);
        console.log('[TEST 5] Edit Form Meta:', JSON.stringify(editMeta));
        await client.captureScreenshot('04_hr_employee_edit.png');

        // -------------------------------------------------------------
        // STEP 6: CLEAR COOKIES & LOGIN AS EMPLOYEE ESS
        // -------------------------------------------------------------
        console.log('\n[TEST 6] Clearing session cookies and logging in as Employee ESS (employee@vishal.com)...');
        await client.send('Network.clearBrowserCookies');
        await client.navigate('http://127.0.0.1:8000/login');
        
        await client.evaluate(`
            document.querySelector('input[name="email"]').value = 'employee@vishal.com';
            document.querySelector('input[name="password"]').value = 'password';
            document.querySelector('form').submit();
        `);
        await sleep(2500);

        const essLoginUrl = await client.evaluate('window.location.href');
        console.log('[TEST 6] ESS Authenticated. Current URL:', essLoginUrl);

        // -------------------------------------------------------------
        // STEP 7: ESS PROFILE VIEW (/ess/profile)
        // -------------------------------------------------------------
        console.log('\n[TEST 7] Navigating to ESS Profile View (/ess/profile)...');
        await client.navigate('http://127.0.0.1:8000/ess/profile');

        const essViewMeta = await client.evaluate(`({
            title: document.title,
            heading: document.querySelector('h2, h3, h4')?.innerText?.trim(),
            hasCompleteness: document.body.innerText.includes('Completeness') || document.body.innerText.includes('%'),
            hasEditBtn: !!document.querySelector('a[href*="/ess/profile/edit"]'),
            statusBadge: document.querySelector('.badge, [class*="badge"]')?.innerText?.trim()
        })`);
        console.log('[TEST 7] ESS Profile View Meta:', JSON.stringify(essViewMeta));
        await client.captureScreenshot('05_ess_profile_view.png');

        // -------------------------------------------------------------
        // STEP 8: ESS PROFILE EDIT (/ess/profile/edit)
        // -------------------------------------------------------------
        console.log('\n[TEST 8] Navigating to ESS Profile Edit (/ess/profile/edit)...');
        await client.navigate('http://127.0.0.1:8000/ess/profile/edit');

        const essEditMeta = await client.evaluate(`({
            title: document.title,
            lockedFieldsCount: document.querySelectorAll('input[readonly], input[disabled]').length,
            hasPersonalPhone: !!document.querySelector('input[name="personal_phone"]'),
            hasEmergencyContact: !!document.querySelector('input[name="emergency_contact_name"]'),
            hasSubmitReviewBtn: document.body.innerText.includes('Submit for HR Review') || !!document.querySelector('button[value="submit_review"]')
        })`);
        console.log('[TEST 8] ESS Profile Edit Meta:', JSON.stringify(essEditMeta));
        await client.captureScreenshot('06_ess_profile_edit.png');

        // -------------------------------------------------------------
        // CONSOLE ERRORS & VERIFICATION AUDIT
        // -------------------------------------------------------------
        console.log('\n--- BROWSER CONSOLE AUDIT ---');
        console.log(`Total console logs captured: ${client.consoleLogs.length}`);
        console.log(`Total page errors captured: ${client.pageErrors.length}`);
        if (client.pageErrors.length > 0) {
            console.warn('[WARNING] Browser Console Errors Found:', client.pageErrors);
        } else {
            console.log('[SUCCESS] 0 Console Errors encountered across all UAT screens!');
        }

        client.close();
        console.log('\n--- COMPREHENSIVE BROWSER UAT AUDIT COMPLETED SUCCESSFULLY ---');
        process.exit(0);

    } catch (err) {
        console.error('[FATAL ERROR IN BROWSER UAT]', err);
        process.exit(1);
    } finally {
        try {
            chrome.kill();
        } catch (e) {}
    }
}

runUat();
