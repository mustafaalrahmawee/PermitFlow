# PermitFlow

A gov-tech case-management and citizen-request portal. Citizens submit formal applications or permit requests and follow their progress transparently; staff members review submitted requests, ask for missing information, record decisions, and keep progress up to date; administrators manage user accounts, request categories, and organization settings.

v1 is a single-node, synchronous system — no queue, scheduler, or async worker. Notifications are in-portal.

## Repository layout

| Path | What it is |
|---|---|
| `api/` | Laravel 13 (PHP 8.4) JSON API — Sanctum bearer-token auth, PostgreSQL, MinIO (S3-compatible) document storage |
| `app/` | Nuxt 4 / Vue 3 / TypeScript SPA — shadcn-vue + Tailwind CSS, Pinia, Vee-Validate + Zod |
| `docs/domain/` | The six domain specs (project context, miniworld, business rules, use cases, data model, system design) — the source of truth |
| `docs/by-use-case/` | Compiled per-use-case implementation contracts (UC-00 … UC-14) |
| `docs/conventions.md` | Always-true project conventions for backend and frontend code |
| `docker-compose.yml` | Local stack: API, PostgreSQL, MinIO |

## Getting started

Prerequisites: Docker, Node.js, and (optionally, for running artisan/tests on the host) PHP 8.4 + Composer.

### 1. Start the backend stack

```bash
docker compose up -d
```

This brings up:

- **api** — Laravel dev server at http://localhost:8000
- **db** — PostgreSQL 16 at localhost:5432 (database `api`, user `root`, trust auth)
- **minio** — S3 API at http://localhost:9000, web console at http://localhost:9001 (`minioadmin` / `minioadmin`); the `permitflow` bucket is created automatically

On first run, install dependencies and build the schema:

```bash
cd api
composer install
php artisan migrate:fresh --seed
```

### 2. Start the frontend

The Nuxt app runs natively on the host (not in a container) and reaches the API at `http://localhost:8000/api`:

```bash
cd app
npm install
npm run dev
```

## Development

Backend (from `api/`):

```bash
composer test        # Pest test suite (sqlite in-memory; no Docker required)
vendor/bin/pint      # code style (Laravel Pint)
./verify-foundation.sh   # end-to-end foundation check: migrate + seed, data-layer checks, auth smoke test
```

Frontend (from `app/`):

```bash
npm run typecheck
npm run build
```

## How this project is built

Implementation is spec-driven. The domain specs in `docs/domain/` define the business rules and use cases; each use case is compiled into a self-contained contract in `docs/by-use-case/` and implemented against that contract, following the conventions in `docs/conventions.md`. See `CLAUDE.md` for the working conventions and architecture summary.
