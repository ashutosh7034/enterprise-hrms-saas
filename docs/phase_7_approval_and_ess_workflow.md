# PHASE 7 APPROVAL WORKFLOW & EMPLOYEE SELF-SERVICE (ESS) SPECIFICATION

**Enterprise Human Resource Management System (HRMS)**  
**Document Reference:** `DOCS-PH7-APP-001`  
**Date:** November 2026  

---

## 1. Approval State Machine

Documents generated from templates flagged with `requires_approval = true` (or generated on behalf of staff) enter a formal governance review pipeline:

```
                  ┌───────────────┐
                  │    Draft      │
                  └──────┬────────┘
                         │ (Submit)
                         ▼
             ┌─────────────────────────┐
             │    Pending Approval     │
             └──────┬─────────────┬────┘
                    │             │
        (Approve)   │             │   (Reject with Reason)
                    ▼             ▼
             ┌────────────┐ ┌────────────┐
             │   Issued   │ │  Rejected  │
             └──────┬─────┘ └────────────┘
                    │
        (Revoke)    │
                    ▼
             ┌────────────┐
             │ Cancelled  │
             └────────────┘
```

### 1.1 State Descriptions
1. **Pending Approval:** Visible exclusively in the Administrative Approval Queue (`/admin/documents/approvals`). Hidden completely from ESS.
2. **Issued:** Approved by HO Admin or HR Executive. The PDF binary is compiled and persisted; a notification is dispatched to the employee; the record becomes downloadable in ESS "My Documents".
3. **Rejected:** Denied authorization with a mandatory explanation recorded in the immutable audit trail.
4. **Cancelled / Revoked:** An issued document is formally retracted. Its public verification status transitions to `Revoked`.

---

## 2. Employee Self-Service (ESS) Architecture

### 2.1 "My Documents" Portal (`/documents/my-documents` or `/ess/documents`)
- Employees can view a structured grid/table of all documents issued to their profile.
- Displays Document Title, Document Number, Category, Issue Date, and Verification Token.
- Direct actions: View snapshot in browser, Download PDF binary.

### 2.2 Strict Security Boundary
- ESS users are strictly quarantined to their own employee ID (`$doc->employee_id === $authEmployee->id`).
- Unauthorized URL manipulation attempting to access another employee's document ID returns `403 Forbidden`.
- Documents in `Draft`, `Pending Approval`, or `Rejected` states return `403 Forbidden` if requested by ESS users.

