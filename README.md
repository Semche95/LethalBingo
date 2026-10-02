<p align="center">
  <img src="assets/img/logo.png" alt="LethalBingo" width="128" />
</p>

<h1 align="center">LethalBingo</h1>

<p align="center">
  Create, manage and play bingo games in the world of <strong>Lethal Company</strong>.
  <br /><br />
  <a href="https://github.com/Semche95/LethalBingo/actions/workflows/ci.yml"><img src="https://github.com/Semche95/LethalBingo/actions/workflows/ci.yml/badge.svg" alt="CI" /></a>
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&amp;logoColor=white" alt="PHP 8.4" />
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue" alt="License: MIT" /></a>
</p>

<p align="center">
  <a href="#screenshots">Screenshots</a> ·
  <a href="#features">Features</a> ·
  <a href="#technology">Technology</a> ·
  <a href="#architecture">Architecture</a> ·
  <a href="#quick-start">Quick Start</a> ·
  <a href="#roadmap">Roadmap</a>
</p>

---

## Description

**LethalBingo** is a web application for organizing and playing 5×5 bingo games set in the world of **Lethal Company**. A **referee** controls the game in real time (square reveal, validation, timer management), while **players** access the game via a unique URL and watch the board update live.

## Screenshots

### Home

<img src="assets/img/screenshots/homepage.png" alt="Home" width="600" />

### Referee view

<img src="assets/img/screenshots/referee-dashboard.png" alt="Referee dashboard" width="600" />

## Features

### Players

- Access the game via a **unique URL** (12-character slug)
- 5×5 bingo board with **squares revealed in real time** by the referee
- **Timer synced** with server time, with pause/resume/adjust controls
- **Visual and audio notifications** tied to the timer (color changes, animations, sounds)
- Instant board, timer, and step updates via **Mercure (SSE)**

### Referee

- **Dashboard** with an overview (games, bingos, teams)
- **Game management** (CRUD): create, edit, delete, regenerate link
- **Bingo management** (CRUD): 5×5 boards per season/round, square editor, duplicate and gap detection
- **Team management** (CRUD): members, single captain, search
- **Real-time controls**: start/stop/reset a game, reveal/hide/validate/invalidate squares, manage the timer
- **Steps panel**: remaining squares per category with quick navigation
- **Notes area** with auto-save

### Security

- Role hierarchy: `ROLE_ADMIN > ROLE_REFEREE > ROLE_USER`
- Login form with **CSRF** protection and **throttling**
- `/referee/*` routes restricted to `ROLE_REFEREE`, `/admin/*` routes to `ROLE_ADMIN`
- Custom error pages (403, 404, 500) with themed GIFs

## Technology

### Backend

| Component | Technology | Version |
|---|---|---|
| Language | PHP | >= 8.4 |
| Framework | Symfony | 7.4.* |
| ORM | Doctrine ORM | ^3.1 |
| Migrations | Doctrine Migrations | ^3.3 |
| Database | PostgreSQL | 16 |
| Server | FrankenPHP (Caddy) | 1.9 |
| Real-time | Symfony Mercure Bundle | ^0.3.9 |
| Messenger | Symfony Messenger + Doctrine transport | * |
| Mailer | Symfony Mailer | * |
| Notifier | Symfony Notifier | * |
| Validation | Symfony Validator | * |
| Rate Limiter | Symfony Rate Limiter | * |
| Logger | Symfony Monolog Bundle | ^3.0 |
| Internationalization | symfony/intl + twig/intl-extra | * / ^3.9 |
| Code Generation | Symfony Maker Bundle | ^1.0 |
| Profiling | Symfony Web Profiler Bundle | * |
| Doctrine Extensions | beberlei/doctrineextensions | ^1.5 |
| Vite | pentatrion/vite-bundle | ^8.2 |

### Frontend

| Component | Technology | Version |
|---|---|---|
| Language | TypeScript | ^5.8.3 |
| Build | Vite | ^6.4.3 |
| Vite Plugin | vite-plugin-symfony | ^8.0 |
| CSS | Bootstrap | ^5.3.6 |
| UI Kit | Tabler Core | ^1.2.0 |
| Turbo | Hotwired Turbo | ^8.0.0 |
| Stimulus | @hotwired/stimulus | ^3.0.0 |
| Stimulus Bridge | @symfony/stimulus-bridge | ^3.2.0 / ^4.0.0 |
| Live Components | @symfony/ux-live-component | (vendor) |
| UX Turbo | @symfony/ux-turbo | ^2.27 |
| UX Icons | symfony/ux-icons | ^2.17 |
| UX Twig Components | symfony/ux-twig-component | ^2.17 |
| UX Live Component | symfony/ux-live-component | ^2.17 |
| PDF Generation | pdfmake | ^0.2.20 |
| ZIP Handling | jszip | ^3.10.1 |

### Tests / Quality

| Tool | Version | Usage |
|---|---|---|
| PHPUnit | ^13 | Unit and functional tests (`just test`) |
| PHPStan | ^2.2 | Static analysis, level 6 (`just phpstan`) |
| PHP-CS-Fixer | ^3.95 | Code style, `@Symfony` + PHP 8.4 migration rules (`just cs` / `just cs-fix`) |
| Rector | ^2.6 | Automated upgrades: PHP 8.4 syntax, Symfony / Doctrine / PHPUnit deprecations (`just rector` / `just rector-fix`) |
| Vitest | ^4.1.11 | Frontend tests (TypeScript) |
| FakerPHP | ^1.23 | Test data generation |
| Doctrine Fixtures | ^3.6 | Database fixtures |

### Infrastructure

| Service | Image | Usage |
|---|---|---|
| `php` | FrankenPHP (custom) | Application server (dev + prod) |
| `database` | `postgres:16-alpine` | Database |
| `mailer` | `axllent/mailpit` | Catch-all SMTP (development) |

PHP extensions installed: `apcu`, `intl`, `opcache`, `zip`, `pdo_pgsql`, `xdebug` (dev)

## Architecture

```mermaid
flowchart LR
    subgraph Browsers
        J["🎮 Players"]
        A["🎛️ Referee"]
    end

    subgraph Server
        F["FrankenPHP + Caddy + Mercure"]
    end

    DB[("PostgreSQL 16")]

    J -- "SSE (real-time)" --> F
    A -- "HTTP / Live Components" <--> F
    F -- "Doctrine ORM" --> DB
```

> **Mercure** (SSE via the Caddy module) pushes real-time updates. Each game has a dedicated topic (`game/{slug}`) and events (`bingoCardRefresh`, `gameTimerRefresh`, `gameStepsRefresh`, etc.) are broadcast to all connected clients. On the client side, a **Stimulus controller** subscribes to the Mercure stream and triggers updates to Symfony **Live Components**.

## Quick Start

### Prerequisites

- PHP 8.4+
- Node.js + pnpm
- Docker + Docker Compose
- [just](https://github.com/casey/just) (task runner)

### Installation

```bash
# Clone the repository
git clone https://github.com/Semche95/LethalBingo.git
cd LethalBingo

# Install JS dependencies
pnpm install

# Create .env.local with your secrets
cp .env .env.local
# Set APP_SECRET, MERCURE_JWT_SECRET, POSTGRES_PASSWORD and DATABASE_URL (see docs/SECURITY.md)

# Start Docker + the dev server
just dev
```

### Available Commands

| Command | Description |
|---|---|
| `just dev` | Starts Docker + Vite dev server |
| `just up` | Starts Docker containers |
| `just down` | Stops containers |
| `just test` | Runs PHPUnit tests |
| `just phpstan` | Runs PHPStan static analysis |
| `just cs` | Checks code style with PHP-CS-Fixer (dry run, shows the diff) |
| `just cs-fix` | Fixes code style with PHP-CS-Fixer |
| `just rector` | Checks the code with Rector (dry run, shows diff) |
| `just rector-fix` | Applies Rector upgrades (modern syntax, deprecations) |
| `just sf <cmd>` | Runs a Symfony command |
| `just migrate` | Applies Doctrine migrations |
| `just diff` | Generates a migration from entities |
| `just fixtures` | Reloads fixtures (truncate + reload) |
| `just cc` | Clears Symfony cache |
| `just build-assets` | Builds assets for production |
| `just logs` | Shows Docker logs |

## Project Structure

```
src/
├── Controller/              # HTTP Controllers (routing attributes)
│   └── Referee/             # Referee area
├── DataFixtures/            # Test fixtures
├── Dto/                     # Data Transfer Objects
├── Entity/                  # Doctrine Entities
├── Form/                    # Symfony Form Types
├── Repository/              # Doctrine Repositories
├── Service/                 # Business logic (BingoService, GameService, etc.)
├── Twig/Components/         # Symfony UX Live Components
└── Validator/               # Custom validation constraints

templates/
├── layouts/                 # Layouts (base, main, referee)
├── game/                    # Game view (player)
├── referee/                 # Referee area views
├── components/              # Live Component templates
└── security/                # Login

assets/
├── controllers/             # Stimulus controllers
│   ├── game/                # Real-time controllers (Mercure, timer, notes)
│   └── referee/             # Referee form controllers
├── icons/                   # SVG icons (Tabler, MDI, Symfony)
├── img/                     # Images and themed GIFs
└── sounds/                  # Audio files (timer)
```

## Database

```mermaid
erDiagram
    User {
        int id PK
        string username UK
        json roles
        string password
    }
    Rule {
        int id PK
        string label
    }
    Team {
        int id PK
        string name UK
    }
    TeamMember {
        int id PK
        string name
        bool captain
        int team_id FK
    }
    GameRound {
        int id PK
        int number
        int season
    }
    Game {
        int id PK
        string slug UK
        int team_id FK
        int round_id FK
        datetime startedAt
        datetime finishedAt
        text notes
        bool isPaused
        float totalPausedSeconds
        int manualAdjustmentSeconds
        datetime lastPausedAt
    }
    BingoSquareCategory {
        int id PK
        text label UK
        int position
    }
    BingoSquare {
        int id PK
        text label
        int row
        int col
        bool visible
        int round_id FK
        int category_id FK
    }
    BingoSquareRevealed {
        int id PK
        int game_id FK
        int bingo_square_id FK
        datetime revealedAt
    }
    BingoSquareValidated {
        int id PK
        int game_id FK
        int bingo_square_id FK
        datetime validatedAt
    }

    Team ||--o{ TeamMember : "has members"
    Team ||--o{ Game : "plays"
    GameRound ||--o{ Game : "played as"
    GameRound ||--o{ BingoSquare : "contains"
    BingoSquareCategory |o--o{ BingoSquare : "categorizes"
    Game ||--o{ BingoSquareRevealed : "reveals"
    BingoSquare ||--o{ BingoSquareRevealed : "revealed in"
    Game ||--o{ BingoSquareValidated : "validates"
    BingoSquare ||--o{ BingoSquareValidated : "validated in"
```

- Doctrine entities with ManyToOne/OneToMany relationships; `User` (authentication) and `Rule` (game rules list) have no relationship
- **Uniqueness constraints**: User (username), Team (name), TeamMember (team, name), Game (slug), Game (team, round), BingoSquareCategory (label), BingoSquare (round, row, col), BingoSquareRevealed (game, bingoSquare), BingoSquareValidated (game, bingoSquare)
- **Custom DQL functions**: `json_agg`, `json_build_object`, `unaccent` (in `src/Dql/`) and `string_agg` (from `beberlei/doctrineextensions`)

## Security

- Passwords hashed with the `auto` algorithm (bcrypt/argon2)
- CSRF protection on all mutations (double-submit cookie)
- Login throttling enabled
- Role hierarchy: `ROLE_ADMIN > ROLE_REFEREE > ROLE_USER`
- Secrets managed via `.env.local` (never committed)
- See [docs/SECURITY.md](docs/SECURITY.md) for best practices

## Roadmap

Tasks (completed and upcoming) are managed with [Backlog.md](https://github.com/MrLesk/Backlog.md) in the [`.backlog/`](.backlog/) folder. It is a pinned dev dependency: after `pnpm install`, run `pnpm exec backlog board` to display the Kanban board, or `pnpm exec backlog browser` for the web interface.

## License

The source code of this project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

Some files are third-party works and are **not** covered by the MIT License: the Lethal Company logo and favicons, GIF images, sounds and icons. See [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) for their owners and licenses.

Lethal Company is a game developed by Zeekerss. LethalBingo is an unofficial fan project, not affiliated with or endorsed by Zeekerss.
