# Phase 6 User Acceptance Testing (UAT) Checklist: Notification Engine
**Enterprise HRMS — Multi-Channel Notification Verification & Sign-Off**  
**Date:** November 2, 2026  
**Auditor / Domain Lead:** Senior HRMS Business Analyst & QA Lead  

---

## 1. Role-Based UAT Verification Matrix

### 1.1 Employee ESS (Self-Service)

| Test ID | Test Scenario | Step-by-Step Action | Expected Result | Status |
|---|---|---|---|---|
| **UAT-ESS-01** | Topbar Notification Bell & Unread Badge | Log in as `employee@vishal.com`. Observe topbar. | Bell icon visible with red badge showing unread count. | **PASS** |
| **UAT-ESS-02** | Notification Dropdown Feed | Click bell icon. | Dropdown opens showing 6 most recent notifications with icons, titles, bodies, and time ago. | **PASS** |
| **UAT-ESS-03** | Mark Single Notification as Read | Open dropdown, click notification or "Mark as read". | Notification status updates to read, unread badge count decreases. | **PASS** |
| **UAT-ESS-04** | Mark All Notifications as Read | In dropdown or `/notifications`, click "Mark all read". | All unread notifications marked as read; red badge disappears. | **PASS** |
| **UAT-ESS-05** | View Personal Notification History | Navigate to `/notifications`. | Paginated table/list displays only logged-in employee's notifications. | **PASS** |
| **UAT-ESS-06** | Filter Notifications by Category | Select category "Payroll" or "Leave". | Filtered feed renders only items matching selected category. | **PASS** |
| **UAT-ESS-07** | Configure Channel Preferences | Navigate to `/notifications/preferences`. Toggle non-mandatory categories. | Preferences saved successfully; toggles persist across sessions. | **PASS** |
| **UAT-ESS-08** | Attempt to Disable Mandatory Alert | Inspect "Compliance" and "Security" rows in preferences. | Toggles are disabled/locked with "Mandatory" badge; opt-out prohibited. | **PASS** |
| **UAT-ESS-09** | Unauthorized Administrative Access | Attempt to browse `/admin/notifications`. | System returns HTTP 403 Forbidden. | **PASS** |

---

### 1.2 Manager MSS (Manager Self-Service)

| Test ID | Test Scenario | Step-by-Step Action | Expected Result | Status |
|---|---|---|---|---|
| **UAT-MSS-01** | Manager Leave Submission Alert | Subordinate employee submits leave application. | Manager receives in-app alert with action link to `/leaves/approvals`. | **PASS** |
| **UAT-MSS-02** | Attendance Regularization Alert | Subordinate submits regularization request. | Manager receives regularization alert in feed. | **PASS** |
| **UAT-MSS-03** | Unauthorized Admin Controls | Manager attempts to visit `/admin/notifications/templates`. | System blocks with HTTP 403 Forbidden. | **PASS** |

---

### 1.3 HR Executive (Company-Level Operations)

| Test ID | Test Scenario | Step-by-Step Action | Expected Result | Status |
|---|---|---|---|---|
| **UAT-HR-01** | Access Notification Control Tower | Log in as `hr@vishal.com`, navigate to `/admin/notifications`. | Access granted. Dashboard renders success rates, metrics, and failure alerts. | **PASS** |
| **UAT-HR-02** | View Outbound Delivery Logs | Navigate to `/admin/notifications/logs`. | Full delivery history displayed for Company VE (Vishal Enterprise). | **PASS** |
| **UAT-HR-03** | Retry Failed Notification | Find failed log in dashboard/logs, click "Retry". | Attempt number increments; message re-dispatched via mock provider; marked Delivered. | **PASS** |
| **UAT-HR-04** | Edit Message Template | Browse `/admin/notifications/templates`, edit `LEAVE_APPROVED`. | Template subject/body updated; audit record logged in `audit_logs`. | **PASS** |
| **UAT-HR-05** | Live Message Preview | Click "Preview" on SMS or WhatsApp template. | Interactive device shell renders message with substituted employee placeholders. | **PASS** |
| **UAT-HR-06** | Cross-Company Isolation | Attempt to retry or edit delivery log belonging to Company VRE. | Scoped out via `BelongsToCompany` (HTTP 404/403). | **PASS** |

---

### 1.4 HO Admin (Multi-Company Governance)

| Test ID | Test Scenario | Step-by-Step Action | Expected Result | Status |
|---|---|---|---|---|
| **UAT-HO-01** | Multi-Company Overview | Log in as `ho_admin@enterprise.com`, switch between `VE` and `VRE`. | Control tower displays aggregated metrics or switches company context seamlessly. | **PASS** |
| **UAT-HO-02** | Configure Channel Gateways | Navigate to `/admin/notifications/channels`. | HO Admin can configure SMS/WhatsApp gateway providers and mock switches for all companies. | **PASS** |
| **UAT-HO-03** | Global Template Auditing | Inspect templates across all categories and channels. | System lists templates with company-specific overrides and global defaults. | **PASS** |

---

## 2. UAT Sign-Off Summary

- **Total Scenarios Evaluated:** 20
- **Passed Scenarios:** 20 (100%)
- **Failed Scenarios:** 0
- **User Experience Assessment:** Clean, responsive, warm-neutral canvas adhering strictly to the design system. Interactive bell and dropdown work smoothly without page reloads.

**Final UAT Sign-Off Status:** **ACCEPTED & APPROVED**

