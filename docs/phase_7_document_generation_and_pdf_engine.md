# PHASE 7 DOCUMENT GENERATION & PDF ENGINE ARCHITECTURE

**Enterprise Human Resource Management System (HRMS)**  
**Target Environment:** Dompdf | Laravel 13.39.0 | Private Disk Storage  
**Document Reference:** `DOCS-PH7-PDF-001`  
**Date:** November 2026  

---

## 1. Generation Engine Lifecycle (`DocumentGenerationService`)

The generation workflow operates deterministically inside a database transaction:

```
[Template + Employee + Custom Data]
                  │
                  ▼
   [Placeholder Engine Resolution]
                  │
                  ▼
  [Immutable Snapshot HTML & JSON]
                  │
                  ▼
       [Unique Sequence & Token]
   (DOC-VE-2026-00001 & VE-DOC-XXXXXXXX)
                  │
        ┌─────────┴─────────┐
        ▼                   ▼
 [Requires Approval]   [Direct Issuance]
        │                   │
        │             [Dompdf Render]
        │                   │
        │             [Save PDF Binary]
        │           (Private Disk Storage)
        ▼                   ▼
[Pending Approval]   [Issued & In ESS]
```

---

## 2. PDF Rendering Engine (`Dompdf\Dompdf`)

### 2.1 Technical Specifications
- **Package:** `Dompdf\Dompdf`
- **Paper Format:** A4 (`210mm x 297mm`), Portrait orientation.
- **Margins:** Top 22mm, Left 18mm, Right 18mm, Bottom 22mm.
- **Typography:** Standard Helvetica / DejaVu Sans with UTF-8 character encoding.
- **Page Break Rules:** Strict CSS rules `page-break-inside: avoid;` applied to table rows, header blocks, signatory sections, and the verification footer.
- **Watermark & Verification Bar:** Every issued document displays an authentic corporate verification block with the unique verification code and public URL.

---

## 3. Secure File Storage Architecture

### 3.1 Private Storage Path
Generated PDF documents are never stored in the public web root (`public/`). They are placed on the `local` private disk:
```
storage/app/documents/{company_id}/{year}/{document_number}.pdf
```

### 3.2 Access Mediation via Controllers
All downloads and visual inspections are streamed through authorized controller actions:
- **Admin:** `DocumentGenerationController@downloadPdf`
- **Employee ESS:** `EmployeeDocumentEssController@downloadPdf`

Both controllers execute `DocumentGenerationService::authorizeAccess(...)`, which verifies:
1. Multi-Company Tenancy (active company match)
2. Role privileges (HR/HO Admin has full access, Manager has departmental/subordinate access, ESS has own issued record access)
3. Lifecycle state (draft or pending documents cannot be downloaded by ESS employees).

