# Final Deployment Checklist

## 1. Server & Runtime
- [ ] PHP 8.x enabled
- [ ] MySQL/MariaDB configured
- [ ] Apache or Nginx vhost pointed to `public/`
- [ ] HTTPS/SSL active
- [ ] Production error display disabled
- [ ] Timezone verified

## 2. Database
- [ ] Import `database/schema.sql` for fresh install
- [ ] Or run any missing phase migration files if upgrading an older DB
- [ ] Create backups before migration
- [ ] Confirm all new tables exist
- [ ] Confirm foreign keys and indexes are intact

## 3. Config
Update `config/config.php`:
- [ ] `app.base_url`
- [ ] `db.host`
- [ ] `db.database`
- [ ] `db.username`
- [ ] `db.password`
- [ ] `mail.from_email`
- [ ] `mail.from_name`
- [ ] `security.document_signing_key`
- [ ] `services.google_maps_key`
- [ ] `services.twilio_sid`
- [ ] `services.twilio_token`
- [ ] `services.twilio_from`
- [ ] `services.stripe_public_key`
- [ ] `services.stripe_secret_key`
- [ ] `services.paypal_client_id`
- [ ] `services.paypal_client_secret`
- [ ] `services.jazzcash_*`
- [ ] `services.easypaisa_*`
- [ ] `services.oauth_google.*`
- [ ] `services.oauth_facebook.*`
- [ ] `services.oauth_twitter.*`
- [ ] `services.ai_model_url`

## 4. Writable Paths
Ensure these are writable by the web server:
- [ ] `public/uploads/`
- [ ] `public/uploads/reports/`
- [ ] `public/uploads/scans/`
- [ ] `public/uploads/signatures/`
- [ ] `public/uploads/media/`
- [ ] `storage/logs/`

## 5. Web Server
### Apache
- [ ] `mod_rewrite` enabled
- [ ] `public/.htaccess` working

### Nginx
- [ ] Rewrites route all non-file requests to `public/index.php`

## 6. First-Time Setup
- [ ] Open app home page
- [ ] Create first admin via `?route=setup-admin`
- [ ] Sign in as admin
- [ ] Verify admin dashboard loads correctly

## 7. Core Functional Testing
### Auth
- [ ] Patient registration
- [ ] Doctor registration
- [ ] Hospital registration
- [ ] Login/logout
- [ ] Forgot password flow

### Patient
- [ ] Dashboard loads
- [ ] Appointment booking works
- [ ] Reports upload works
- [ ] AI scanner saves history
- [ ] Payment invoice flow works
- [ ] Health profile save/delete actions work
- [ ] Consent management works

### Doctor
- [ ] Dashboard loads
- [ ] Appointment updates work
- [ ] Consultation room works in browser
- [ ] Clinical records generate
- [ ] Signature profile saves
- [ ] Verification code pages open

### Hospital
- [ ] Hospital dashboard loads
- [ ] Department save works
- [ ] Inventory save works
- [ ] Bed save works
- [ ] Affiliation approvals work
- [ ] Consent-based patient record access works

### Admin
- [ ] Verification center works
- [ ] Content management works
- [ ] Compliance center works
- [ ] Notification templates save
- [ ] Refund management works

## 8. Public Content Testing
- [ ] Home page loads
- [ ] FAQ page loads
- [ ] Guidelines page loads
- [ ] Blog list and article detail pages load
- [ ] Disease library and disease detail pages load
- [ ] Policy pages load
- [ ] Search page returns results
- [ ] `sitemap.xml` works
- [ ] `robots.txt` works

## 9. External Services
### Google Maps
- [ ] Billing enabled on Google Cloud
- [ ] Maps JavaScript API enabled
- [ ] Places API enabled
- [ ] Geocoding API enabled
- [ ] Directions API enabled
- [ ] Referrer restrictions configured

### PHPMailer / SMTP
- [ ] PHPMailer installed in runtime
- [ ] SMTP credentials tested
- [ ] `storage/logs/mail.log` checked

### Twilio
- [ ] SDK installed in runtime
- [ ] Sender number verified
- [ ] SMS test successful
- [ ] `storage/logs/sms.log` checked

### Payments
- [ ] Sandbox provider credentials tested
- [ ] Webhook/callback implementation plan prepared
- [ ] Mock payments disabled for production if needed

## 10. Security Hardening
- [ ] Move secrets out of repo config into environment strategy if possible
- [ ] Regenerate session security settings for production
- [ ] Enforce secure cookies
- [ ] Add SameSite policy
- [ ] Add rate limiting at web server/app gateway
- [ ] Add WAF/CDN if available
- [ ] Restrict upload mime types further if needed
- [ ] Regular DB backups enabled
- [ ] Log rotation configured

## 11. SEO & Public Quality
- [ ] Metadata reviewed for key pages
- [ ] Sitemap submitted to search consoles
- [ ] Social preview tags checked
- [ ] Broken links checked
- [ ] Canonical URLs verified

## 12. Performance
- [ ] Enable gzip/brotli
- [ ] Add browser caching headers
- [ ] Optimize images
- [ ] Consider CDN for static assets
- [ ] Add database query review for high-traffic pages

## 13. Browser / Device Validation
- [ ] Chrome
- [ ] Firefox
- [ ] Edge
- [ ] Safari
- [ ] Android mobile
- [ ] iPhone/iPad

## 14. Final Launch Decision
- [ ] All critical flows tested
- [ ] Production credentials installed
- [ ] Backups verified
- [ ] Monitoring/log review in place
- [ ] Launch approved
