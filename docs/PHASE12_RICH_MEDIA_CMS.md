# Phase 12 - Rich Text and Media Management for CMS

This phase upgrades the CMS so public content is no longer limited to plain text blocks.

## Implemented in this phase
- Admin media library uploads
- Supported media upload types:
  - JPG
  - PNG
  - WebP
  - GIF
  - PDF
- Rich text editor for admin article and disease content
- HTML sanitization for CMS content before storage
- Featured image support for:
  - articles
  - disease information pages
- SEO fields for:
  - articles
  - disease information pages
- Blog cards now support featured images
- Article detail pages now render rich content
- Disease library and disease detail pages now support featured images and rich content sections

## Database additions
New table:
- `media_assets`

Updated tables:
- `cms_articles`
- `disease_information`

Migration:
- `database/migrations/phase12_rich_media_cms.sql`

## Notes
- Rich text is sanitized before saving to reduce unsafe HTML injection risk.
- Media upload and selection currently use path-based referencing from the library.
- This phase creates the base for future drag-and-drop page builders or gallery-driven content editing.

## Recommended next phase
- Advanced patient profile and longitudinal health metrics
- or stronger compliance / policy workflow automation
