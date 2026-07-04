# Phase 4 - Notifications, Email, and SMS Foundation

This phase adds a multi-channel notification layer to MediSphere with in-app alerts, email delivery architecture, SMS/Twilio hooks, and admin-managed templates.

## Implemented in this phase
- In-app notification center
- Header bell dropdown with unread count
- AJAX polling for latest notifications
- Mark single notification as read
- Mark all notifications as read
- Per-user notification preferences
- Category preferences for:
  - appointments
  - payments
  - security alerts
  - marketing updates
- Preferred notification language field
- Admin communication template management
  - email templates
  - SMS templates
- Mail service with:
  - PHPMailer auto-detection when installed
  - native `mail()` fallback
  - delivery logging to `storage/logs/mail.log`
- SMS service with:
  - Twilio SDK auto-detection when installed
  - credential validation
  - delivery logging to `storage/logs/sms.log`
- Notification service orchestration used by core workflows

## Workflow hooks connected
Notifications are now triggered for:
- patient appointment submission
- doctor receiving new appointment request
- appointment status updates to patient
- invoice generation
- payment success
- payment pending state
- doctor payment received notice
- refund request submission to admins
- refund request updates to patients
- doctor verification updates
- hospital verification updates

## Database additions
New tables:
- `notifications`
- `notification_preferences`
- `email_templates`
- `sms_templates`

Migration:
- `database/migrations/phase4_notifications.sql`

## Local setup
If you already imported earlier phases, run:
- `database/migrations/phase4_notifications.sql`

If you are doing a fresh setup, import:
- `database/schema.sql`

## PHPMailer setup
The app automatically uses PHPMailer **if the class exists** and `mail.driver` is set to `smtp`.

Typical next step:
- install PHPMailer with Composer in your local project
- configure SMTP credentials in `config/config.php`

## Twilio setup
The app automatically uses Twilio **if the SDK class exists** and Twilio credentials are configured.

Set in `config/config.php`:
- `twilio_sid`
- `twilio_token`
- `twilio_from`

## Notes
- In-app notifications are fully functional now.
- Email/SMS delivery architecture is production-oriented, but actual PHPMailer/Twilio SDK packages must be installed in your runtime environment for live delivery.
- Without those packages, the system falls back gracefully and logs delivery attempts.

## Recommended next phase
- WebRTC live consultation module
