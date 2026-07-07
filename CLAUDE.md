# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PermitFlow is a gov-tech portal where citizens submit permit requests, staff review and decide them, and administrators manage accounts, categories, and settings. Monorepo:

- `api/` — Laravel 13 (PHP 8.4) JSON API, Sanctum bearer-token auth, PostgreSQL, MinIO (S3) for documents.
- `app/` — Nuxt 4 / Vue 3 / TypeScript SPA: shadcn-vue + Tailwind, Pinia, Vee-Validate + Zod.
- `docs/` — the spec set that drives all implementation (see "Spec-driven workflow").
- `.claude/skills/` — the project's three pipeline skills.

v1 is single-node and synchronous: no queue, scheduler, or async worker.

## Commands

Local stack (Postgres :5432, MinIO :9000/console :9001, API :8000):

```bash
docker compose up -d           # from repo root; api container runs `php artisan serve`
```

Host PHP 8.4 also works directly against the compose services (`api/.env` points at 127.0.0.1/localhost).

Backend (from `api/`):

```bash
composer test                          # Pest suite (sqlite in-memory — no Docker needed)
php artisan test --filter=SomeTest     # single test
vendor/bin/pint                        # lint/format (Laravel Pint)
php artisan migrate:fresh --seed       # rebuild schema + dev fixture (needs db up)
./verify-foundation.sh                 # full foundation check: install, migrate+seed, tinker checks, auth smoke test
```

Frontend (from `app/`, runs on the host, not in Docker):

```bash
npm run dev          # expects API at http://localhost:8000/api (NUXT_PUBLIC_API_BASE)
npm run typecheck
npm run build
```

## Spec-driven workflow

Implementation is contract-driven, not ad-hoc:

- `docs/domain/00…05` — the six domain specs (source of truth: miniworld, business rules BR-NNN, use cases UC-NN, data model, system design).
- `docs/by-use-case/ucNN_*.md` — self-contained per-use-case contracts compiled from the specs. A use-case session reads its contract, **not** the six specs.
- `docs/conventions.md` — always-true project conventions. **Read it before writing backend or frontend code**; the summary below is not a substitute.
- Skills pipeline: `/foundation` (run once, already done) → `/domain-doc-generator <domain>` (compiles contracts) → `/use-case-implementer <UC-NN>` (implements one use case, backend then frontend, each gated by a QA subagent; never commits).

Generated code that would contradict a spec is a conflict to report, not a free choice. When writing docs/spec/role files, state general rules — do not embed concrete code snippets as examples.

## Backend architecture (api/)

- **Auth:** Sanctum personal access tokens on `App\Models\UserAccount` (not the skeleton `users` table, which remains only for framework integrity). Protected routes use `auth:sanctum`; no session/SPA-cookie auth.
- **Authorization fails closed:** native policies + role gates registered in `AppServiceProvider`, no permissions package. Request-scoped reach (`InteractsWithRequestScope`): a record is visible only to its owning citizen, responsible staff member, or an administrator — out-of-scope reads return **404**, not 403.
- **Status transitions** go through one guard: the `TransitionsRequestStatus` trait on `Request` holds the allowed-transition map; `transitionTo` mutates in memory only and throws `IllegalStatusTransitionException` on illegal targets. The caller persists the status change **and its history entry in one transaction**.
- **History is explicit:** `request_history_entries` rows are written in use-case logic with a frozen `summary`; never auto-logged, never regenerated. Unique on (`request_id`, `sequence_number`).
- **Enums:** string-backed PHP enums in `app/Enums` (snake_case slug values, `varchar` columns with casts); each carries `label()`, `values()`, `options()`.
- **Schema:** bigint auto-increment keys; every FK uses `restrictOnDelete()`; every Eloquent relation names its FK column explicitly; accounts are deactivated (`inactive`), never deleted.
- **Response envelope:** success is always `{ data, message }` — never an ad-hoc resource key. Paginated lists put the flat array in `data` plus a sibling `meta` (`current_page`, `last_page`, `per_page`, `total`); never nest the raw paginator. Errors use Laravel's native `{ message }` (+ `{ errors }` on 422) and never include `data`. Error-code mapping (401/403/404/422/409/500) is specified in `docs/conventions.md`.
- **Storage:** documents go to the S3 disk against MinIO; `documents.file_reference` stores the object key. Validate the reference belongs to the in-scope request before serving.
- **Dependencies are deliberately minimal:** only `laravel/sanctum` and `league/flysystem-aws-s3-v3` beyond the skeleton. Do not add permissions/activity-log/state-machine packages.

## Frontend architecture (app/)

- **One API client:** `app/app/plugins/api.ts` configures `$fetch` with the base URL and Sanctum bearer token. Callers pass only a path — no per-call headers or base URL, no fetch composable.
- **Fetching lives in Pinia stores** (setup-style, in `app/app/stores`), never in pages. Pages are presentational: call store actions, read state via `storeToRefs`. After a mutation, patch the store's local collection (append/replace by id) — do not refetch the list.
- **Types first:** domain interfaces and enum slug→label option maps live in `app/app/types` and are imported everywhere.
- **Routing:** one global middleware protects every page via an allowlist of public page names; authenticated pages declare no layout/middleware. A public/guest page must be registered in **two** places: the allowlist, and the guest layout + guest middleware.
