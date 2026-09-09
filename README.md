# Reel
<img width="1366" height="655" alt="image" src="https://github.com/user-attachments/assets/dd77a8ae-79c1-4e67-9cfa-03f7be95b11f" />

<img width="1366" height="649" alt="image" src="https://github.com/user-attachments/assets/11e1b532-87e9-4ce1-9725-86d529fdf58b" />
<p align="center">
  <a href="#features">Features</a> •
  <a href="#advanced-search">Advanced Search</a> •
  <a href="#controls">Controls</a> •
  <a href="#tv-shows">TV Shows</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#project-structure">Project Structure</a> •
  <a href="#running-locally">Running Locally</a> •
  <a href="#tmdb-configuration">TMDB Configuration</a> •
  <a href="#performance">Performance</a> •
  <a href="#data-and-attribution">Attribution</a> •
  <a href="#playback">Playback</a> •
  <a href="#license">License</a>
</p>
<p align="center"> <img src="https://img.shields.io/github/last-commit/aabhro/Reel.svg?style=flat-square&logo=github&logoColor=white" alt="Last commit"> <img src="https://img.shields.io/github/issues-raw/aabhro/Reel.svg?style=flat-square&logo=github&logoColor=white" alt="Open issues"> <img src="https://img.shields.io/github/issues-pr-raw/aabhro/Reel.svg?style=flat-square&logo=github&logoColor=white" alt="Pull requests"> </p>

A minimal, canvas-driven movie and TV discovery interface powered by TMDB.

Reel turns browsing into an interactive canvas instead of a conventional poster grid. Drag, scroll, search, filter, inspect titles, choose TV episodes, and start playback from a single lightweight page.

## Features

* Endless, wrapping poster canvas with drag and scroll navigation
* Momentum-based movement and depth-style poster distortion
* Movie and TV show discovery
* Popular, trending, top-rated, and newest feeds
* TMDB title search
* Genre and minimum-rating filters
* Grouped pill-style filter controls
* Slash-tag advanced search
* Search tags for type, genre, sort, year, decade, and rating
* TV season and episode selector
* Title details with poster, backdrop, overview, cast, genres, runtime, score, votes, and trailer
* Fullscreen playback
* Local caching of loaded titles
* Progressive poster loading
* Cached genre lists
* Idle-aware canvas rendering for smoother navigation
* Responsive mobile layout
* Reduced-motion support
* Bottom gradual blur with a soft fade into the background
* Single-file frontend with no framework

## Advanced search

Search accepts regular text and slash-prefixed filters in the same query.

```text
batman /movie /crime
```

```text
stranger things /tv /horror
```

```text
dune /movie /sci-fi /2021 /8+
```

```text
/tv /top /2020s
```

Supported filter types:

| Syntax      | Description      |
| ----------- | ---------------- |
| `/tv`       | TV shows         |
| `/movie`    | Movies           |
| `/popular`  | Popular titles   |
| `/top`      | Top-rated titles |
| `/trending` | Trending titles  |
| `/new`      | Newest titles    |
| `/horror`   | Genre filter     |
| `/action`   | Genre filter     |
| `/sci-fi`   | Genre filter     |
| `/2020`     | Release year     |
| `/2020s`    | Release decade   |
| `/8+`       | Minimum rating   |

Genre tags are resolved against the TMDB genre list, with common aliases supported as well.

Search also works without tags:

```text
blade runner
```

## Controls

| Input        | Action                               |
| ------------ | ------------------------------------ |
| Drag         | Move around the canvas               |
| Scroll       | Move through the canvas              |
| `/`          | Open search                          |
| `Esc`        | Close the active interface or player |
| `←` `→`      | Move horizontally                    |
| `↑` `↓`      | Move vertically                      |
| Click poster | Open title details                   |

## TV shows

TV titles use the same details flow as movies, with an additional grouped season and episode selector.

Selecting a season refreshes the episode list immediately. The selected season and episode are passed to the player when playback starts.

## Tech stack

* HTML5
* CSS3
* Vanilla JavaScript
* Canvas 2D API
* TMDB REST API
* Native Fetch API
* Manrope

The frontend does not use React, Vue, Tailwind, or a component library.

## Project structure

```text
.
└── reel.html
```

The application is intentionally kept in a single HTML file. Markup, styles, application state, canvas rendering, TMDB requests, search parsing, filtering, details, TV controls, and player integration all live in the same document.

## Running locally

Open the project through a local HTTP server rather than directly from `file://`.

```bash
python -m http.server 8080
```

Then open:

```text
http://localhost:8080/reel.html
```

## TMDB configuration

The client currently reads the TMDB API key from the script:

```js
const TMDB_API_KEY = "your-key";
```

For a public deployment, keep the API credential server-side and proxy TMDB requests through your own backend.

## Performance

The canvas avoids running a permanent animation loop while idle. Rendering resumes when the scene changes, when the user drags or scrolls, or when new images finish loading.

The renderer also uses reusable tile objects, cached poster images, cached genre responses, direct world-to-movie index assignments, and a capped device pixel ratio to keep the workload predictable.

The gradual blur uses a small number of stacked `backdrop-filter` layers rather than relying on a framework component or an extra rendering dependency.

## Data and attribution

Movie and TV metadata, genres, posters, and backdrops are provided by TMDB.

Reel is not affiliated with or endorsed by TMDB.

## Playback

Reel uses an external embed service for playback. Movie IDs and TV IDs are passed to the configured player, with the selected TV season and episode included for television content.

## License

No license has been added to this repository yet.

Choose a license before distributing the project publicly if you want to define reuse and redistribution terms.
