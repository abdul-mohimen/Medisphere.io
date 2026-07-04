# Phase 7 - Clinical Tools, Prescriptions, and Certificates

This phase expands the doctor workflow with structured clinical records generation and patient-accessible medical documents.

## Implemented in this phase
- Doctor-facing **Clinical Records** hub
- Patient-facing **My Clinical Records** view
- Printable prescription generation
- Printable clinical document generation
- Supported clinical document types:
  - Medical certificate
  - Discharge summary
  - Treatment plan
  - Follow-up note
- Structured medication capture (one line per medication)
- Digital signature block using doctor name + license
- Follow-up date support in prescriptions
- Draft / finalized status for prescriptions and documents
- Patient access to doctor-issued prescriptions and clinical documents
- Dashboard counters for prescriptions and clinical documents

## Database additions
New tables:
- `prescriptions`
- `clinical_documents`

Migration:
- `database/migrations/phase7_clinical_tools.sql`

## Routes added
- `clinical-records`
- `clinical-records/prescription`
- `clinical-records/document`
- create/save routes for prescriptions and clinical documents

## Notes
- Browser print is used for PDF export of prescriptions and clinical documents.
- This phase focuses on the clinical generation workflow rather than server-side PDF libraries.
- Notifications are sent to patients when a doctor generates a prescription or clinical document.

## Recommended next phase
- Advanced hospital management and departments
- or Admin CMS/content management expansion
