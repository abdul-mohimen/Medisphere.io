# Project Structure

## Root
- `README.md` â€” project overview and setup
- `FINAL_DEPLOYMENT_CHECKLIST.md` â€” go-live checklist
- `PROJECT_STRUCTURE.md` â€” this file
- `PRODUCTION_GAPS.md` â€” remaining live-integration gaps

## App Layer
### `app/Core/`
Framework-style core utilities.
- `Auth.php` â€” session auth and guards
- `CSRF.php` â€” CSRF token generation/validation
- `Database.php` â€” PDO bootstrap
- `helpers.php` â€” global helper functions
- `HtmlSanitizer.php` â€” sanitized rich HTML handling
- `Language.php` â€” localization + RTL support
- `Security.php` â€” sanitization helpers
- `View.php` â€” simple view renderer

### `app/Controllers/`
Request/route handlers.
- `HomeController.php` â€” landing, FAQ, guidelines, map
- `AuthController.php` â€” auth, registration, reset password
- `DashboardController.php` â€” role dashboards
- `AppointmentController.php` â€” appointments
- `ScannerController.php` â€” AI scanner history/save
- `ReportController.php` â€” report upload/listing
- `MessageController.php` â€” chat polling/send
- `HealthcareController.php` â€” directory + facility API
- `PaymentController.php` â€” invoices, payments, refunds
- `NotificationController.php` â€” notifications + preferences + templates
- `ConsultationController.php` â€” WebRTC consultation rooms
- `ClinicalController.php` â€” prescriptions, clinical docs, verification
- `HospitalManagementController.php` â€” departments, inventory, beds, affiliations, consent-based access
- `RecordConsentController.php` â€” patient consent management
- `PatientProfileController.php` â€” advanced health profile save/delete actions
- `AdminController.php` â€” verification workflows
- `AdminContentController.php` â€” CMS/content management
- `ComplianceController.php` â€” policies, privacy requests, incidents
- `ContentController.php` â€” public articles/disease content
- `SearchController.php` — live smart-search suggestions API
- `SystemController.php` â€” sitemap/robots
- `LocalizationController.php` â€” language switching
- `SetupController.php` â€” first admin creation

### `app/Models/`
Database models by domain.

#### Core Identity / Messaging
- `User.php`
- `Patient.php`
- `Doctor.php`
- `Hospital.php`
- `ChatMessage.php`
- `AdminLog.php`

#### Medical / Appointment / Reports
- `Appointment.php`
- `MedicalReport.php`
- `DiseaseScan.php`
- `Prescription.php`
- `ClinicalDocument.php`
- `ConsultationSession.php`
- `ConsultationSignal.php`
- `ConsultationFeedback.php`
- `DoctorAvailability.php`

#### Payments / Billing
- `Invoice.php`
- `Payment.php`
- `RefundRequest.php`

#### Notifications / Templates
- `Notification.php`
- `NotificationPreference.php`
- `EmailTemplate.php`
- `SmsTemplate.php`

#### Hospital Operations
- `DoctorHospitalRequest.php`
- `HospitalDepartment.php`
- `HospitalInventoryItem.php`
- `HospitalDoctorAssignment.php`
- `HospitalBedUnit.php`
- `PatientRecordConsent.php`
- `PatientRecordAccessLog.php`

#### Patient Advanced Profile
- `PatientHistoryEvent.php`
- `PatientAllergy.php`
- `PatientMedication.php`
- `PatientEmergencyContact.php`
- `PatientInsuranceProfile.php`
- `PatientFamilyHistory.php`
- `PatientHealthMetric.php`
- `PatientProfileShare.php`

#### Document Security / Compliance / CMS
- `DoctorSignatureProfile.php`
- `DocumentAuditLog.php`
- `CmsFaq.php`
- `CmsArticle.php`
- `DiseaseInformation.php`
- `SiteSetting.php`
- `MediaAsset.php`
- `PolicyDocument.php`
- `UserPolicyAcknowledgement.php`
- `DataPrivacyRequest.php`
- `ComplianceIncident.php`

### `app/Services/`
Cross-cutting services.
- `Mailer.php` â€” mail() / PHPMailer
- `SMSService.php` â€” Twilio integration layer
- `NotificationService.php` â€” in-app/email/SMS orchestration
- `Payments/` â€” gateway architecture
  - `GatewayInterface.php`
  - `PaymentManager.php`
  - `MockGateway.php`
  - `StripeGateway.php`
  - `PayPalGateway.php`
  - `JazzCashGateway.php`
  - `EasyPaisaGateway.php`

### `app/Views/`
Template files.

#### Public
- `home.php`
- `faq.php`
- `guidelines.php`
- `map.php`
- `blog_index.php`
- `article_view.php`
- `disease_library.php`
- `disease_view.php`
- `policy_index.php`
- `policy_view.php`
- `error404.php`

#### Auth / User Portals
- `auth.php`
- `reset_password.php`
- `dashboard.php`
- `profile.php`
- `health_profile.php`
- `patient_public_profile.php`
- `appointments.php`
- `reports.php`
- `scanner.php`
- `messages.php`
- `payments.php`
- `invoice.php`
- `notifications.php`
- `consultations.php`
- `consultation_room.php`
- `clinical_records.php`
- `prescription_view.php`
- `clinical_document_view.php`
- `verify_document.php`
- `record_consents.php`
- `hospital_management.php`
- `hospital_patient_record.php`

#### Admin
- `admin_verifications.php`
- `admin_content.php`
- `admin_compliance.php`
- `notification_templates.php`
- `setup_admin.php`

#### Layouts
- `layouts/main.php`

## Config
### `config/`
- `config.php` â€” active config
- `config.example.php` â€” sample config

## Database
### `database/`
- `schema.sql` â€” full latest schema
- `migrations/` â€” phase-by-phase SQL patches
  - `phase3_payments.sql`
  - `phase4_notifications.sql`
  - `phase5_consultations.sql`
  - `phase7_clinical_tools.sql`
  - `phase8_hospital_management.sql`
  - `phase9_admin_cms.sql`
  - `phase10_document_security.sql`
  - `phase11_hospital_analytics_consents.sql`
  - `phase12_rich_media_cms.sql`
  - `phase13_patient_profile_metrics.sql`
  - `phase14_compliance_controls.sql`

## Public Web Root
### `public/`
- `index.php` â€” front controller/router
- `.htaccess` â€” Apache rewrite rules
- `assets/css/app.css` â€” main UI stylesheet
- `assets/js/app.js` â€” main JS bundle
- `uploads/` â€” stored uploaded files
  - `reports/`
  - `scans/`
  - `signatures/`
  - `media/`
  - `avatars/`

## Localization
### `resources/lang/`
Language packs.
- `en.php`
- `ur.php`
- `hi.php`
- `ar.php`
- `es.php`
- `fr.php`
- `zh.php`
- `de.php`
- `partials/` â€” shared translation arrays for structured homepage content

## Storage
### `storage/logs/`
- `mail.log`
- `sms.log`
- `notifications.log`

## Current Completion Snapshot
The project now includes:
- multi-role auth
- dashboards
- appointments
- reports
- AI scanner scaffold
- maps
- billing
- notifications
- telemedicine
- multilingual UI
- clinical tools
- hospital ops
- CMS
- document integrity
- patient health profile
- compliance center
- SEO/search public experience
