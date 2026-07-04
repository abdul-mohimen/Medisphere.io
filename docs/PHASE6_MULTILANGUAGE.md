# Phase 6 - Multi-language + RTL Foundation

This phase adds a reusable localization system to MediSphere with runtime language switching and RTL support.

## Implemented in this phase
- Localization core with language loader and fallback handling
- Runtime language switching from the header selector
- Browser-language auto-detection on first visit
- Session + cookie persistence for selected locale
- RTL support for:
  - Urdu
  - Arabic
- Supported language files:
  - English
  - Urdu
  - Hindi
  - Arabic
  - Spanish
  - French
  - Chinese
  - German
- Translation helpers:
  - `trans()`
  - `__()`
  - `current_locale()`
  - `is_rtl()`
  - `supported_locales()`
- Shared UI translated in the main layout
- Landing page translated through language files
- Auth page headings/tabs translated
- FAQ and guideline headings translated
- Chatbot quick replies and canned answers localized
- Selected locale synced to notification preferences for signed-in users
- Full active-page translation fallback for rendered text, visible attributes, and dynamically inserted content

## Files added
- `app/Core/Language.php`
- `app/Controllers/LocalizationController.php`
- `resources/lang/*.php`
- `resources/lang/partials/*`
- `docs/PHASE6_MULTILANGUAGE.md`

## Updated
- `app/bootstrap.php`
- `app/Core/helpers.php`
- `config/config.php`
- `config/config.example.php`
- `public/index.php`
- `app/Views/layouts/main.php`
- `app/Views/home.php`
- `app/Views/auth.php`
- `app/Views/faq.php`
- `app/Views/guidelines.php`
- `app/Services/PageTranslationService.php`
- `public/assets/css/app.css`
- `public/assets/js/app.js`

## Notes
- The architecture still supports adding curated translation keys page by page without changing the routing system.
- A page-wide translator now covers hard-coded text that does not have a curated key yet, including placeholders, alt/title/ARIA labels, button values, AJAX content, modals, and dropdown content.
- Remote fallback translation can be disabled with `features.page_translator_remote` or redirected with `services.page_translator_endpoint`.
- RTL layout handling is already wired for Urdu and Arabic.

## Next recommended phase
- Clinical tools / prescriptions / certificates
- or Hospital management expansion
