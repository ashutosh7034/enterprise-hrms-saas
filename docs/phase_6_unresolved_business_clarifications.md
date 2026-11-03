# Phase 6 Unresolved Business Clarifications & Production Integration Decisions
**Enterprise HRMS — Communication Engine Governance & Carrier Roadmap**  
**Date:** November 2, 2026  
**Auditor / Domain Analyst:** Senior HRMS Business Analyst  

---

## 1. Overview of Unresolved Domain Clarifications

While Phase 6 implements the complete architectural foundation, database schema, pluggable provider interfaces, and UI controls for multi-channel notifications, several production carrier decisions and compliance operational policies require formal business stakeholder sign-off prior to production deployment:

---

### Clarification 1: Production SMS Gateway & Indian DLT Registration
- **Current Architecture:** Pluggable `SmsNotificationProviderInterface` with an active `MockSmsProvider`. The database holds configuration slots for MSG91, Twilio, and Kaleyra.
- **Business Question:** Which telecom aggregator will be selected for production SMS delivery in India?
- **Regulatory Requirement:** Under Telecom Regulatory Authority of India (TRAI) TCCCPR regulations, commercial SMS delivery requires:
  1. Principal Entity (PE) ID registration on the DLT portal (e.g., Jio, Airtel, or Vil DLT).
  2. Approved Sender Headers (e.g., `VISHAL`, `VRISHL`).
  3. Pre-approved DLT Content Template IDs matching message text character-for-character.
- **Architectural Readiness:** The `notification_channel_configurations.settings` JSON schema already supports storing `dlt_entity_id`, `sender_id`, and `dlt_template_id`.

---

### Clarification 2: WhatsApp Business API (WABA) Partner
- **Current Architecture:** Pluggable `WhatsAppNotificationProviderInterface` with an active `MockWhatsAppProvider`.
- **Business Question:** Will Vishal Enterprise and Vrishal Eng. Pvt. Ltd. integrate directly via Meta Cloud API or through a certified Business Solution Provider (BSP) like Gupshup or Wati?
- **Operational Requirement:** WhatsApp mandates that outbound business-initiated messages use pre-approved HSM templates. Free-form text can only be sent within the 24-hour customer service window.
- **Architectural Readiness:** `notification_templates` stores WhatsApp template names and parameters aligned with Meta HSM specifications.

---

### Clarification 3: Quiet Hours & Delivery Suppression Rules
- **Current Architecture:** Instant queued dispatch upon event occurrence.
- **Business Question:** Should non-critical notifications (such as shift rotation previews, comp-off credits, or casual leave approvals) be restricted during "Quiet Hours" (e.g., 22:00 to 07:00 local time) and held in queue until morning?
- **Recommendation:** Exempt statutory compliance, security alerts, and shift-start reminders (within 2 hours) from quiet hour holds, while delaying general administrative announcements until 07:00.

---

### Clarification 4: Notification Retention & Archival Policy
- **Current Architecture:** Append-only log retention in `notification_delivery_logs` and `notifications`.
- **Business Question:** What is the legal and audit retention requirement for notification logs and in-app feeds?
- **Recommendation:**
  - In-App Notifications: Retain for 180 days, then soft-prune read items older than 6 months.
  - Delivery Logs: Retain for 365 days for audit trails, with monthly partition archiving in MySQL.

---

## 2. Summary of Stakeholder Decisions Required

| # | Topic | Responsible Stakeholder | Impact | Target Resolution Date |
|---|---|---|---|---|
| 1 | DLT Registration & SMS Provider Selection | Corporate Communications & IT Lead | Required before toggling `is_mock = false` on SMS | Prior to Production Rollout |
| 2 | WhatsApp WABA Provider Agreement | Finance & IT Infrastructure | Required before live WhatsApp outbound | Prior to Production Rollout |
| 3 | Quiet Hours Policy Sign-off | HR Vice President & Operations Head | Configurable in channel settings | Next Review Meeting |
| 4 | Data Retention Schedule | Chief Information Security Officer (CISO) | Retention cron configuration | Next Review Meeting |

