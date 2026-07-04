# Phase 11 - Hospital Analytics, Bed Management, and Patient Record Consents

This phase deepens hospital operations with capacity tracking, patient record access governance, and auditability.

## Implemented in this phase
- Bed management for hospitals
  - ward name
  - bed label
  - bed type
  - occupancy status
  - assigned patient
  - notes
- Hospital analytics snapshot
  - total appointments
  - completed appointments
  - pending appointments
  - monthly appointments
  - unique patients
  - bed occupancy stats
- Patient-controlled record consents
- Patient consent management page
- Hospital view of active patient consents
- Consent-gated patient record summary access
- Patient record access logging
- Patient-side view of record access history
- Hospital-side operational access logs

## Database additions
New tables:
- `hospital_bed_units`
- `patient_record_consents`
- `patient_record_access_logs`

Migration:
- `database/migrations/phase11_hospital_analytics_consents.sql`

## New routes
- `record-consents`
- patient save/revoke consent actions
- hospital patient record access route

## Notes
- Hospitals can view patient record summaries only if active consent exists.
- Access attempts are written to audit logs with accessor identity and timestamp.
- Bed occupancy and patient flow analytics now give the hospital portal more operational depth.

## Recommended next phase
- Rich text/media management for CMS content
- or advanced patient profile and longitudinal health metrics
