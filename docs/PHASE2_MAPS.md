# Phase 2 - Google Maps Healthcare Discovery

This phase upgrades the platform with a production-ready **maps integration layer** for healthcare discovery.

## Implemented in this phase
- Full-screen **Interactive Healthcare Map** page
- Embedded live map on **Find Healthcare** directory page
- Google Maps JavaScript loader using the configured API key
- Hospital markers from database coordinates
- Doctor clinic markers derived from affiliated hospital coordinates
- Marker selection with rich facility info windows
- Browser geolocation support
- Address/city search with geocoding
- Route generation using Google Directions
- Nearby pharmacy discovery via Places API
- Nearby emergency facility discovery via Places API
- Distance calculation from the user’s current location
- Directory card to map sync: **Focus on Map** and **Get Directions**
- Graceful fallback when no API key is configured or preview networking is unavailable

## Configuration
Set your Google Maps key in:

`config/config.php`

```php
'services' => [
    'google_maps_key' => 'YOUR_GOOGLE_MAPS_API_KEY',
]
```

## Required Google APIs
Enable these in Google Cloud:
- Maps JavaScript API
- Geocoding API
- Places API
- Directions API

Also ensure:
- billing is enabled
- HTTP referrers are configured for your local/dev/prod domains

## Coordinate format
Hospital coordinates should be stored in the database as:

```text
31.5204,74.3587
```

## Notes
- Doctor markers are positioned around hospital coordinates with slight offsets to avoid overlap.
- Nearby Places results are fetched live from Google and are not stored in the local DB.
- The in-app workspace preview may block network-based Google Maps loading; local XAMPP/browser execution is the correct test path.

## Suggested next phase after maps
- payment integration
- PHPMailer + Twilio notifications
- live WebRTC consultation
- multilingual + RTL
