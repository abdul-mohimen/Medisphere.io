# Social Login Setup

Create a local `.env` file in the project root, next to `.env.example`, and paste your OAuth credentials there.

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/healthcare-platform/public/index.php?route=auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://localhost/healthcare-platform/public/index.php?route=auth/facebook/callback

X_CLIENT_ID=
X_CLIENT_SECRET=
X_REDIRECT_URI=http://localhost/healthcare-platform/public/index.php?route=auth/x/callback
```

Provider callback URLs to register:

- Google: `http://localhost/healthcare-platform/public/index.php?route=auth/google/callback`
- Facebook: `http://localhost/healthcare-platform/public/index.php?route=auth/facebook/callback`
- X: `http://localhost/healthcare-platform/public/index.php?route=auth/x/callback`

The PHP config reads these values through `config/env.php`, then exposes them through `config/config.php` under `services.oauth_google`, `services.oauth_facebook`, and `services.oauth_x`.

Google and Facebook accounts create/sign in patient users using the provider email. X OAuth 2 does not normally return an email address, so MediSphere creates a stable local patient identity using the X user ID.
