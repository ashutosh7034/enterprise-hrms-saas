# ENTERPRISE HRMS — DATABASE INTEGRITY REPORT

**Date:** 2026-11-03  
**Databases Inspected:** `enterprise_hrms` (Development), `enterprise_hrms_test` (Testing)  
**Governing Rule:** Read-Only Audit — Zero Destructive Mutations Executed  

---

## 1. Audit Scope & Verification Checks

A structured integrity scan was performed across all primary business domains:
1. Assets without valid company mapping.
2. Orphaned asset allocation records (allocation pointing to missing asset or employee).
3. Assets with multiple active allocations (duplicate concurrent active allocations).
4. Employees without valid department mappings.
5. Employees without valid company mappings.
6. Cross-company allocation mismatches (Asset company != Allocation company OR Employee company != Allocation company).
7. Asset status vs Active allocation consistency.
8. Damage assessments with invalid allocation references.
9. Fine recoveries with invalid damage assessment references.

---

## 2. Integrity Audit Results

### 2.1 Development Database (`enterprise_hrms`)

| Verification Check | Target Tables | Records Inspected | Anomalies Found | Details / Resolution Required |
| :--- | :--- | :--- | :--- | :--- |
| **Assets without valid company** | `assets`, `companies` | 5 | **0** | All assets belong to valid enterprise entities |
| **Orphaned asset allocations** | `asset_allocations`, `assets`, `employees` | 27 | **0** | All allocations link to valid assets and employees |
| **Multiple concurrent active allocations** | `asset_allocations` | 27 | **0** | Zero duplicate concurrent active allocations |
| **Employees with invalid department** | `employees`, `departments` | 522 | **0** | All employees mapped to valid departments |
| **Employees with invalid company** | `employees`, `companies` | 522 | **0** | Multi-company tenant mapping is 100% sound |
| **Cross-company allocation mismatches**| `asset_allocations`, `assets`, `employees` | 27 | **0** | Zero cross-tenant data leaks |
| **Damage assessments with invalid alloc**| `asset_damage_assessments`, `asset_allocations` | 1 | **0** | Foreign key integrity preserved |
| **Fine recoveries with invalid assessment**| `fine_recovery_records`, `asset_damage_assessments`| 2 | **0** | Foreign key integrity preserved |
| **Asset status vs Active allocation** | `assets`, `asset_allocations` | 5 | **2** | **Data Anomaly Identified (Non-Breaking)** |

#### Asset Status Discrepancy Details in `enterprise_hrms`:
1. **Asset `VE-AST-001` (ID 1)**:
   - `assets.status` = `'Available'`
   - `asset_allocations` contains 1 record with `status` = `'Active'` (Allocation ID 27, Employee ID 4).
   - *Impact*: In the UI, the active allocation is displayed, while the badge reflects 'Available'.
2. **Asset `VE-AST-002` (ID 2)**:
   - `assets.status` = `'Allocated'`
   - `asset_allocations` contains 0 records with `status` = `'Active'` (previous allocation was returned).
   - *Impact*: In the UI, the badge reflects 'Allocated', but no current employee is listed.

*Remediation Recommendation*:  
Execute the following non-destructive status synchronization once authorized by stakeholder:
```sql
-- Sync status based on real active allocations:
UPDATE assets SET status = 'Allocated' WHERE id = 1;
UPDATE assets SET status = 'Available' WHERE id = 2;
```

---

### 2.2 Test Database (`enterprise_hrms_test`)

| Verification Check | Records Inspected | Anomalies Found |
| :--- | :--- | :--- |
| Assets without valid company | 4 | **0** |
| Orphaned asset allocations | 28 | **0** |
| Multiple concurrent active allocations | 28 | **0** |
| Employees with invalid department | 522 | **0** |
| Employees with invalid company | 522 | **0** |
| Cross-company allocation mismatches | 28 | **0** |
| Damage assessments with invalid alloc | 1 | **0** |
| Fine recoveries with invalid assessment | 2 | **0** |
| Asset status vs Active allocation | 4 | **1** (`VE-AST-001`) |

---

## 3. Immutability & Safety Attestation

In accordance with Phase 8 release governance:
- No database rows were deleted.
- No database tables were truncated or dropped.
- The development database (`enterprise_hrms`) remains unmutated.
- Destructive cleanup will only occur upon explicit stakeholder sign-off.
