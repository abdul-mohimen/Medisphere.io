# Phase 13 - Advanced Patient Profile and Longitudinal Health Metrics

This phase expands the patient portal into a richer health profile workspace with structured personal health data and trend tracking.

## Implemented in this phase
- Advanced patient health profile page
- Medical history timeline entries
- Allergies list
- Current medications tracker
- Emergency contact profile
- Insurance profile
- Family medical history tracking
- Longitudinal health metrics tracker
  - weight
  - blood pressure
  - sugar
  - oxygen
  - temperature
  - heart rate
- Metric trend chart for recent entries
- Quick profile access share token
- Public quick-access patient summary page
- QR-based access badge for the shared patient summary

## Database additions
New tables:
- `patient_history_events`
- `patient_allergies`
- `patient_medications`
- `patient_emergency_contacts`
- `patient_insurance_profiles`
- `patient_family_history`
- `patient_health_metrics`
- `patient_profile_shares`

Migration:
- `database/migrations/phase13_patient_profile_metrics.sql`

## New routes
- patient profile save/delete actions
- `profile/public?token=...`

## Notes
- QR rendering uses a public QR image service for convenience in browser environments.
- The share token can be regenerated at any time to invalidate older links.
- The patient profile now acts as a longitudinal personal health workspace instead of a basic editable card.

## Recommended next phase
- Stronger compliance / policy workflow controls
- or SEO / automation / public experience enhancements
