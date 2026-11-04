# ENTERPRISE HRMS SaaS — PHASE 3 SUBSCRIPTION ARCHITECTURE SPECIFICATION
## Licensing & Subscriptions Engine Architecture

**Product:** Enterprise HRMS SaaS  
**Platform Owner:** Advait IT Solutions & Services  
**Document:** System Architecture Specification (Phase 3)  
**Date:** November 3, 2026  
**Status:** Approved Architecture  

---

## 1. ARCHITECTURAL OVERVIEW

The Licensing & Subscriptions Engine provides the commercial and entitlement foundation for Advait IT Solutions & Services. It bridges platform administrative controls with tenant operational capabilities, enforcing license validity, user limits, and module permissions without compromising multi-tenant security boundaries.

```mermaid
flowchart TD
    subgraph PlatformControl["Advait IT Solutions Platform Layer"]
        PSA["Platform Super Admin"] --> PlanMgmt["Subscription Plan Management"]
        PSA --> SubMgmt["Tenant Subscription Oversight"]
        PSA --> InvMgmt["Invoice & Payment Records"]
    end

    subgraph DataLayer["Subscription & Entitlement Models"]
        Plan["SubscriptionPlan"] -->|defines| Feat["SubscriptionFeature (Entitlements)"]
        Plan -->|assigned via| Sub["TenantSubscription"]
        Tenant["Customer Tenant (e.g. Vishal Group)"] -->|owns| Sub
        Sub -->|generates| Inv["SubscriptionInvoice"]
        Inv -->|settled by| Pay["SubscriptionPayment"]
    end

    subgraph Enforcement["License Enforcement Pipeline"]
        SubValid["EnsureTenantSubscriptionIsValid"] --> StatusCheck{"Status Check"}
        StatusCheck -->|Active| Allow["Permit Request"]
        StatusCheck -->|Grace Period| Warn["Permit + Warning Banner"]
        StatusCheck -->|Expired / Suspended / Cancelled| Block["Block Request + Evict Session"]
        
        ModValid["EnsureModuleEntitlement"] --> FeatCheck{"Module Enabled?"}
        FeatCheck -->|Yes| RouteAccess["Execute Controller"]
        FeatCheck -->|No| ModBlock["HTTP 403 Forbidden"]
    end

    PlanMgmt --> Plan
    SubMgmt --> Sub
    InvMgmt --> Inv
    Sub --> SubValid
```

---

## 2. ENTITY RELATIONSHIP DIAGRAM (ERD)

```mermaid
erDiagram
    tenants ||--o{ tenant_subscriptions : "holds"
    tenants ||--o{ subscription_invoices : "billed to"
    subscription_plans ||--o{ tenant_subscriptions : "defines"
    subscription_plans ||--o{ subscription_features : "contains"
    tenant_subscriptions ||--o{ subscription_invoices : "produces"
    subscription_invoices ||--o{ subscription_payments : "settled with"
    platform_users ||--o{ tenant_subscriptions : "assigned by"

    subscription_plans {
        bigint id PK
        string plan_code UK
        string name
        text description
        enum billing_interval
        decimal price
        string currency
        int employee_limit
        int company_limit
        enum status
        json metadata
        timestamps created_at
    }

    subscription_features {
        bigint id PK
        bigint subscription_plan_id FK
        string feature_code
        string feature_name
        boolean is_enabled
        json metadata
        timestamps created_at
    }

    tenant_subscriptions {
        bigint id PK
        bigint tenant_id FK
        bigint subscription_plan_id FK
        string subscription_code UK
        enum status
        datetime starts_at
        datetime ends_at
        datetime grace_period_ends_at
        datetime activated_at
        datetime suspended_at
        datetime cancelled_at
        enum renewal_type
        text notes
        json metadata
        bigint created_by_platform_user_id FK
        timestamps created_at
    }

    subscription_invoices {
        bigint id PK
        bigint tenant_id FK
        bigint tenant_subscription_id FK
        string invoice_number UK
        decimal amount
        string currency
        enum status
        datetime issued_at
        datetime due_at
        datetime paid_at
        text notes
        json metadata
        timestamps created_at
    }

    subscription_payments {
        bigint id PK
        bigint tenant_id FK
        bigint tenant_subscription_id FK
        bigint subscription_invoice_id FK
        string payment_reference UK
        string payment_provider
        decimal amount
        string currency
        enum status
        datetime paid_at
        json metadata
        timestamps created_at
    }
```

---

## 3. SUBSCRIPTION LIFECYCLE STATE MACHINE

The tenant subscription lifecycle operates as a finite state machine:

```mermaid
stateDiagram-v2
    [*] --> pending : Subscription Assigned
    pending --> active : Platform Admin Activates / Payment Confirmed
    pending --> trial : Trial Provisioned
    trial --> active : Converted to Paid
    trial --> expired : Trial Window Elapsed
    
    active --> grace_period : Current Time > ends_at
    grace_period --> expired : Current Time > grace_period_ends_at
    grace_period --> active : Subscription Renewed
    
    expired --> active : Renewed & Reactivated
    active --> suspended : Platform Admin Suspends
    suspended --> active : Platform Admin Reactivates
    
    active --> cancelled : Cancellation Processed
    suspended --> cancelled : Terminal Cancellation
    expired --> cancelled : Archived
    cancelled --> [*]
```

### State Definitions
1. **`pending`**: Subscription registered but awaiting administrative activation or payment confirmation.
2. **`trial`**: Complimentary evaluation window with full or restricted feature access.
3. **`active`**: Commercial active license. All permitted modules and operations functional.
4. **`grace_period`**: Expiry date has passed, but tenant is granted an operational window (typically 7–14 days) before total lockout. Warning banner rendered.
5. **`expired`**: Validity period and grace window elapsed. Customer HRMS operations locked out; session evicted.
6. **`suspended`**: Customer access administratively frozen (e.g. billing breach or legal hold).
7. **`cancelled`**: Contract terminated. Historical records preserved, operational access blocked.

---

## 4. MODULE & FEATURE ENTITLEMENT TAXONOMY

The platform structures features as configurable keys assigned to plans:

| Feature Code | Human Name | Scope of Coverage |
| :--- | :--- | :--- |
| `employee_management` | Workforce & Onboarding | Employee directory, profile, eKYC, gate pass, blacklist checks |
| `attendance_management`| Time & Attendance | Punch logs, biometric integration, regularization, muster |
| `leave_management` | Leave & Sandwich Policies | Leave balances, accruals, carry forwards, comp-offs, workflow |
| `payroll_management` | Payroll & Statutory Engine| Salary structures, runs, statutory PF/ESI/PT/TDS, payslips |
| `asset_management` | Asset Lifecycle | Asset inventory, allocation, return, depreciation, damage recovery |
| `document_management` | Document Generation & ESS | Document templates, versioning, generation, digital download |
| `reports` | Analytics & Scheduled Reports | Standard reports, Excel/PDF exports, automated cron delivery |
| `recruitment` | Talent Acquisition | Candidate pipeline, job requisitions, offer letters |
| `performance_management`| Appraisal & Goals | KPI tracking, annual reviews, 360 feedback |
| `employee_self_service`| ESS Portal | Personal leave/punch logs, payslip viewing, doc download |
| `manager_self_service` | MSS Portal | Team muster, regularization reviews, leave approval |

---

## 5. LICENSE LIMIT VALIDATION FLOW

### 5.1 Maximum Active Employees Check
```mermaid
sequenceDiagram
    autonumber
    actor HR as HR Executive / Admin
    participant C as EmployeeController
    participant S as LicenseEntitlementService
    participant DB as Database (employees)

    HR->>C: Submit Candidate Onboarding Form
    C->>S: canAddEmployee(companyId)
    S->>DB: Count Active Employees in Tenant Companies
    DB-->>S: Return count (e.g. 142)
    S->>S: Compare count against Plan Limit (e.g. 250)
    alt Count < Limit
        S-->>C: Allowed (true)
        C->>DB: Insert new Employee Record
        C-->>HR: 201 Created (Success)
    else Count >= Limit
        S-->>C: Denied (false, Limit Exceeded)
        C-->>HR: Validation Error: "Tenant employee limit of 250 reached."
    end
```

### 5.2 Maximum Legal Companies Check
```mermaid
sequenceDiagram
    autonumber
    actor Admin as HO Admin / Super Admin
    participant CC as CompanyController
    participant S as LicenseEntitlementService
    participant DB as Database (companies)

    Admin->>CC: Register New Legal Company
    CC->>S: canAddCompany(tenantId)
    S->>DB: Count Companies where tenant_id = tenantId
    DB-->>S: Return count (e.g. 2)
    S->>S: Compare count against Plan Limit (e.g. 3)
    alt Count < Limit
        S-->>CC: Allowed (true)
        CC->>DB: Insert new Company
        CC-->>Admin: 200 Company Created
    else Count >= Limit
        S-->>CC: Denied (false)
        CC-->>Admin: Error: "Maximum company quota reached for this plan."
    end
```

---

## 6. BILLING-READY DESIGN & INVOICING ARCHITECTURE

To ensure seamless future integration with payment gateways such as **Razorpay**, **Cashfree**, or **Stripe**, the billing foundation establishes:
1. **Deterministic Invoice Numbering:** Formatted as `INV-YYYY-XXXXX` (e.g. `INV-2026-00001`).
2. **Provider Abstraction:** The `subscription_payments` table stores `payment_provider` (`manual_bank_transfer`, `offline_cheque`, `razorpay`, `stripe`) and `payment_reference`.
3. **Idempotent Webhook Handling:** Invoices cannot be double-paid. Payments are recorded inside database transactions that immediately advance the subscription status.
4. **Zero PCI DSS Exposure:** No credit card numbers, CVV codes, or net banking passwords are ever stored or processed by the application.

---

## 7. AUDIT TRAIL INTEGRATION

All subscription events are tracked in `platform_audit_logs`:
- `SUBSCRIPTION_PLAN_CREATED`
- `SUBSCRIPTION_PLAN_UPDATED`
- `TENANT_SUBSCRIPTION_ASSIGNED`
- `TENANT_SUBSCRIPTION_ACTIVATED`
- `TENANT_SUBSCRIPTION_RENEWED`
- `TENANT_SUBSCRIPTION_SUSPENDED`
- `TENANT_SUBSCRIPTION_REACTIVATED`
- `TENANT_SUBSCRIPTION_CANCELLED`
- `INVOICE_GENERATED`
- `PAYMENT_RECORDED`

This guarantees complete traceability under Advait IT Solutions & Services management.

