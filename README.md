# Reel

A canvas-driven movie and TV discovery app with multi-profile libraries, per-episode progress, social sign-in and cloud sync.

Reel is open source under the MIT License.

## Features

- Interactive Three.js poster wall and list view
- Movie and TV show discovery through TMDB
- Search, genre, year, rating and personalized discovery filters
- TV season and episode selection
- CineSrc fullscreen playback integration
- Up to eight profiles per account
- Separate watchlists, viewing history and progress for each profile
- Google and Discord sign-in through Supabase Auth
- Cloud-saved account details, profiles, libraries and preferences
- Guest mode remains available without an account
- Netlify Functions proxy TMDB requests so its API key stays server-side
- Supabase Row Level Security isolates each user's saved data

## Architecture

    frontend/
      index.html
      assets/
        css/reel.css
        js/motion.js
        js/reel.js
    netlify/
      functions/
        config.mjs
        tmdb.mjs
    supabase/
      schema.sql
    netlify.toml
    .env.example
    reel.html

The frontend is static HTML, CSS and JavaScript. The backend is made of Netlify Functions. Supabase Auth handles OAuth and Supabase Postgres stores account state.

The browser receives only the Supabase URL and public publishable/anon key. It never receives a service_role key. The database's RLS policies require auth.uid() to match the row's user_id for every operation.

## Deploy directly to Netlify

1. Import this repository into Netlify. Keep the repository root as the base directory.
2. The included netlify.toml sets frontend as the publish directory and netlify/functions as the Functions directory. No build command is required.
3. Create a Supabase project.
4. Open Supabase SQL Editor and run all of supabase/schema.sql.
5. In Supabase Authentication → URL Configuration, set the Site URL to your deployed Netlify URL. Add these patterns to the allowed Redirect URLs, replacing the example host with your own:

- https://your-site.netlify.app/**
- http://localhost:8888/**
- https://**--your-netlify-team.netlify.app/** for deploy previews.
6. Enable Google and Discord under Supabase Authentication → Sign In / Providers. Follow the provider steps below.
7. Add the environment variables below in Netlify project settings. Make sure they are available to Functions.
8. Trigger a new Netlify deploy after adding or changing environment variables.

### Netlify environment variables

| Variable | Purpose |
| --- | --- |
| TMDB_API_KEY | Private TMDB API key used only by the serverless proxy |
| SUPABASE_URL | Supabase project URL |
| SUPABASE_ANON_KEY | Browser-safe publishable/anon key |

The Supabase anon/publishable key is designed for browser use. It is safe only when the database is protected by the included RLS policies. Never place a service_role key in frontend code or public Netlify configuration.

**Rotate the old TMDB key before deployment.** The earlier single-file version committed a TMDB key directly into source code. Git retains previous commits, so removing the key from the latest version does not make the old key private. Revoke or rotate it in TMDB, then put the new key only in Netlify's TMDB_API_KEY environment variable.

OAuth client IDs and secrets belong in Supabase provider settings, not in this repository.

## Configure Google sign-in

1. Create a Web OAuth client in Google Cloud Console.
2. Add your Netlify site origin to Authorized JavaScript origins, for example https://your-site.netlify.app.
3. Copy the Supabase callback URL shown on the Google provider page. It follows this format: https://<project-ref>.supabase.co/auth/v1/callback
4. Add that callback URL to Google's Authorized redirect URIs.
5. Copy the Google Client ID and Client Secret into Supabase Authentication → Sign In / Providers → Google, then enable the provider.

## Configure Discord sign-in

1. Create an application in the Discord Developer Portal.
2. Open OAuth2 and add the Supabase callback URL shown by the Supabase Discord provider page. It follows this format: https://<project-ref>.supabase.co/auth/v1/callback
3. Copy the Discord Client ID and Client Secret into Supabase Authentication → Sign In / Providers → Discord, then enable the provider.

Supabase completes the OAuth exchange and redirects the browser back to Netlify. Reel uses signInWithOAuth for both providers.

## Cloud sync and Guest mode

- On first sign-in, the browser's existing Guest profiles and libraries are uploaded if the account has no saved Reel state.
- Later sign-ins restore that account's profiles, watchlists, history, progress and shared preferences.
- Changes sync after a short debounce, including playback progress updates.
- Each account has one reel_account_state row. RLS prevents a user from reading or changing another account's row.
- Signing out clears the signed-in user's profile data from browser storage and returns Reel to a clean Guest profile. The cloud copy remains stored.
- Guest mode keeps profiles local to the current browser until sign-in.

## Run locally

Copy .env.example to .env and fill in the three values. Then start Netlify Dev:

    npx netlify-cli dev

Netlify Dev serves the frontend and emulates Functions at the same /api/config and /api/tmdb routes. Add your local development URL, commonly http://localhost:8888/**, to Supabase's allowed Redirect URLs and to the Google OAuth JavaScript origins.

## Open source and security

- The project is MIT-licensed. See LICENSE.
- Do not commit .env, OAuth client secrets, or a Supabase service_role key.
- Configure Google and Discord secrets only in Supabase Auth provider settings.
- Configure the TMDB key only as a Netlify environment variable available to Functions.
- Keep the supplied RLS policies enabled. Do not grant anonymous access to reel_account_state.
- Review provider policies, quotas and Terms of Service for TMDB and the playback provider before public launch.

## Attribution

Movie and TV metadata, posters and backdrops are supplied by TMDB. Reel is not affiliated with or endorsed by TMDB.

Playback is provided through an external CineSrc embed. Reel does not host video files.
