# ENTERPRISE HRMS — CUSTOM UI/UX DESIGN SYSTEM SPECIFICATION

## 1. Visual Philosophy & Design Identity

The Enterprise HRMS interface is purpose-built for an industrial engineering and workforce enterprise operating across corporate offices, engineering plants, and ground project sites (**Vishal Enterprise** and **Vrishal Eng. Pvt. Ltd.**).

### Core Aesthetic Principles
- **Warm Neutral Foundation**: Anchored in rich slate, warm stone, and deep industrial charcoal—completely free of generic AI purple-blue gradients or plastic neon highlights.
- **Purposeful Density & Whitespace**: Information-dense yet legible, optimized for HR operations, payroll verifications, and roster scheduling.
- **Restrained Semantic Accents**: Functional color coding where green, amber, red, and blue convey strict operational states (e.g. Present, Exception, Plant Shutdown, Overtime).
- **Physical Authenticity**: Subtle border contrasts, micro-shadows (1px–3px), crisp typography, and crisp tactile buttons.

---

## 2. Design Tokens & Variables

### 2.1 Color Palette

#### Neutral & Surface Tokens (Warm Charcoal & Stone)
- `--surface-canvas`: `#F8F9FA` (Soft warm off-white canvas)
- `--surface-card`: `#FFFFFF` (Crisp pure white surface)
- `--surface-subtle`: `#F1F3F5` (Subtle container background)
- `--surface-hover`: `#E9ECEF` (Hover / table row highlight)
- `--border-subtle`: `#E2E8F0` (Default divider / card border)
- `--border-strong`: `#CBD5E1` (Input borders / active outlines)

#### Brand & Primary Tokens (Industrial Deep Slate)
- `--brand-primary`: `#1E293B` (Deep Slate Slate-900: primary brand headers, sidebar, key actions)
- `--brand-primary-hover`: `#0F172A` (Slate-950)
- `--brand-secondary`: `#475569` (Slate-600: supporting icons, subtitles)
- `--brand-accent`: `#0D9488` (Teal-600: refined operational accent for primary buttons, active tabs, progress bars)
- `--brand-accent-hover`: `#0F766E` (Teal-700)

#### Semantic Status Tokens
- **Success / Present / Verified**:
  - Background: `#ECFDF5` (Emerald-50)
  - Border: `#A7F3D0` (Emerald-200)
  - Text: `#065F46` (Emerald-800)
  - Dot Indicator: `#10B981` (Emerald-500)
- **Warning / Pending / Exception / Grace Period**:
  - Background: `#FFFBEB` (Amber-50)
  - Border: `#FDE68A` (Amber-200)
  - Text: `#92400E` (Amber-800)
  - Dot Indicator: `#F59E0B` (Amber-500)
- **Danger / Absent / Rejected / Blacklisted / LOP**:
  - Background: `#FEF2F2` (Rose-50)
  - Border: `#FECDD3` (Rose-200)
  - Text: `#9F1239` (Rose-800)
  - Dot Indicator: `#F43F5E` (Rose-500)
- **Info / Holiday / Plant Shutdown / Weekly Off**:
  - Background: `#F0FDF4` / `#F8FAFC` (Slate/Indigo-50)
  - Border: `#E2E8F0` (Slate-200)
  - Text: `#334155` (Slate-700)
  - Dot Indicator: `#64748B` (Slate-500)
- **Overtime / Comp-Off**:
  - Background: `#EFF6FF` (Blue-50)
  - Border: `#BFDBFE` (Blue-200)
  - Text: `#1E40AF` (Blue-800)
  - Dot Indicator: `#3B82F6` (Blue-500)

---

### 2.2 Typography Scale

- **Font Family**:
  - Headings & Data: `Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
  - Financial & Code Numbers (Aadhaar, Gate Pass, Salary, Punch Times): `"JetBrains Mono", "SF Mono", Menlo, Consolas, monospace`
- **Scale**:
  - `Display 1`: `28px / 1.2 / font-semibold / tracking-tight` (Dashboard greetings, primary KPIs)
  - `Heading 2`: `22px / 1.3 / font-semibold` (Page headers)
  - `Heading 3`: `18px / 1.4 / font-semibold` (Card section titles, modal titles)
  - `Heading 4`: `15px / 1.4 / font-medium` (Table group headers, subsection titles)
  - `Body Regular`: `14px / 1.5 / font-normal` (Standard table cells, form labels, body text)
  - `Body Small`: `12px / 1.4 / font-normal` (Timestamps, metadata, helper text, badges)
  - `Micro`: `11px / 1.3 / font-medium / tracking-wider / uppercase` (Table column headers, pill tags)

---

### 2.3 Spacing, Borders & Elevation

- **Spacing Scale**:
  - `space-1`: `4px`
  - `space-2`: `8px`
  - `space-3`: `12px`
  - `space-4`: `16px`
  - `space-5`: `24px`
  - `space-6`: `32px`
  - `space-8`: `48px`
- **Border Radius**:
  - Cards & Containers: `8px` (Slightly rounded, crisp industrial edges)
  - Buttons & Form Inputs: `6px`
  - Status Badges / Pills: `9999px` (Full capsule)
- **Shadows**:
  - `elevation-0`: `none`
  - `elevation-card`: `0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05)`
  - `elevation-hover`: `0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05)`
  - `elevation-modal`: `0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05)`

---

## 3. Core Component Specifications

### 3.1 App Shell & Navigation
- **Top Bar**:
  - Height: `60px`
  - Left: Active Company Selector Pill (**Vishal Enterprise** $\leftrightarrow$ **Vrishal Eng. Pvt. Ltd.**)
  - Center: Global Instant Search (Search Employee Name, Code, Gate Pass, Aadhaar, Phone)
  - Right: Quick Punch Status Widget, Notification Bell with unread counter, Profile / Role badge (ESS / MSS / Admin toggle where authorized).
- **Collapsible Sidebar**:
  - Width: `260px` (Expanded), `68px` (Iconized)
  - Color: `#0F172A` (Deep Slate)
  - Grouping:
    - *Core*: Dashboard, My Profile (ESS)
    - *Workforce*: Employee Directory, Onboarding, Documents & eKYC, Blacklist / Whitelist, Asset Clearance
    - *Time & Attendance*: Daily Muster Roll, Shift Roster, Biometric Devices, Plant Shutdowns, Attendance Regularization
    - *Leave & Holidays*: Leave Applications, Comp-Off Credits, Sandwich & Inclusion Rules, Holiday Calendar, Period Locks
    - *Payroll & Finance*: Salary Structures, Payroll Run, Payslips, Loans & Advances, Full & Final (F&F)
    - *Communications*: Notifications Log, Letter & Form Print Engine
    - *Intelligence*: Statutory Compliance Reports, Audit Logs, System Settings

---

### 3.2 Data Tables & Data Grids
- **Header Row**: Background `#F8FAFC`, uppercase `11px` font, slate-500, height `40px`, non-sticky or sticky on scroll.
- **Data Rows**: Background `#FFFFFF`, border-bottom `1px solid #F1F5F9`, hover background `#F8FAFC`, vertical align middle.
- **Row Density Options**: Standard (`48px` row height) vs Compact (`38px` row height for heavy payroll and muster verification).
- **Inline Actions**: Floating action bar on row hover or three-dots popover.
- **Empty State**: Centered illustration icon, warm text header ("No records found for the selected company/filters"), and action button.

---

### 3.3 Status Badges & Indicators
- Compact capsule pills (`padding: 2px 8px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;`):
  - `● Active` (Green dot + light green pill)
  - `● Present` (Emerald dot + light emerald pill)
  - `● Absent` (Rose dot + light rose pill)
  - `● Half Day` (Amber dot + light amber pill)
  - `● On Leave` (Blue dot + light blue pill)
  - `● Plant Shutdown` (Purple/Slate dot + neutral pill)
  - `● eKYC Pending` (Amber warning pill with countdown: "12 days left")
  - `● Exceptionally Verified` (Blue informational pill)
  - `● Blacklisted` (Dark crimson pill with padlock icon)

---

### 3.4 Role-Based Dashboard Layouts

#### 1. Employee Dashboard (ESS)
- **Top Hero Card**: Today's status (Checked In at 08:54 AM, Shift: General 09:00 - 18:00, Location: Plant 1).
- **Leave Balance Grid**: 4 balanced cards (Casual Leave: 4.5 remaining, Sick Leave: 6.0, Paid Leave: 12.0, Comp-Off: 2.0).
- **Recent Applications**: Pending leave/regularization status card with cancel action.
- **Quick Links**: Download Latest Payslip (October 2026), Gate Pass QR code, Holiday list upcoming.

#### 2. Manager / Supervisor Dashboard (MSS)
- **Team Presence Counter**: Present (42), Absent (3), On Leave (2), Late (4), On Duty (1).
- **Urgent Action Queue**: Actionable cards for 1-click Approval/Rejection of Leave, Missing Punch Regularization, Overtime authorization, and Comp-Off requests.
- **Today's Team Shift Roster**: Visual timeline bar showing team coverage across shifts.
- **Exception Summary**: Top frequent late arrivals or missing punches requiring supervisor review.

#### 3. HR & HO Team Admin Hub
- **Multi-Company Metric Strip**: Headcount (Vishal: 620 | Vrishal: 380), Direct vs Contractor ratio pie, Monthly Payroll Status (Ready for Review).
- **Compliance & Risk Watchlist**: eKYC expiring in $\le 7$ days, Blacklist match flags, Unreturned assets of resigned staff.
- **Plant Shutdown Control**: Active and upcoming shutdowns with impacted site worker counts.
- **Payroll Progress Stepper**: 1. Muster Locked $\rightarrow$ 2. Exceptions Cleared $\rightarrow$ 3. Pre-Run Audit $\rightarrow$ 4. Disbursed.

---

### 3.5 Interaction & State Behaviors
- **Form Validation**: Clean inline field validation (error message beneath input in `#E11D48`, input border `#F43F5E`, no disruptive modal alerts).
- **Loading States**: Subtle skeleton loaders with calm shimmer rather than full-page blocking spinners.
- **Confirmation Modals**: Two-step confirmations for destructive actions (Blacklisting candidate, Deleting payroll run, Locking leave period, Terminating employee).
