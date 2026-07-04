# Phase 5 - WebRTC Telemedicine & Consultation Rooms

This phase introduces live consultation architecture with browser-based WebRTC sessions, waiting rooms, signaling, and consultation history.

## Implemented in this phase
- Consultation hub for patients and doctors
- Room creation from appointment context
- Virtual waiting room flow
- Doctor availability status: online / busy / offline
- WebRTC room UI with:
  - local video/audio
  - remote video/audio
  - microphone toggle
  - camera toggle
  - screen sharing
  - end call control
- Signaling layer using AJAX polling + MySQL-backed signal records
- Automatic session activation when both participants join
- Consultation history list
- Ended session feedback/rating form
- Browser-based local recording download (with consent flag)

## Database additions
New tables:
- `doctor_availability`
- `consultation_sessions`
- `consultation_signals`
- `consultation_feedback`

Migration:
- `database/migrations/phase5_consultations.sql`

## Notes
- This phase uses **WebRTC peer connection** plus **database/AJAX signaling** so it works without a separate WebSocket signaling server.
- For higher scale and lower latency, a dedicated signaling server should still be the next upgrade.
- Local recording is saved on the user device; it is not uploaded to the server in this phase.
- Screen sharing is available for video consultation sessions only.

## Recommended next step after this phase
- Multi-language + RTL
- or Clinical tools / prescriptions
