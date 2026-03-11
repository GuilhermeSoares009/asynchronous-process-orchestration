# Asynchronous Process Orchestration

An async workflow orchestrator built for long-running, multi-step pipelines with persistent state, retries, and operational controls.

## Goals

- Run multi-step workflows in background queues
- Persist checkpoints and context per step
- Enforce step idempotency with retries/backoff
- Support pause, cancel, resume, and replay
- Provide operational visibility (logs + metrics)

## Scope

- MVP: start workflow, execute N fixed steps, persist state, retry/backoff, status endpoint
- V1: pause/cancel/resume, DLQ, replay, tenant rate limiting
- V2: compensation steps, simple DAG, circuit breaker, retention policies

## Architecture (High Level)

- WorkflowType defines the list of steps
- WorkflowRun holds the state and context
- Each step runs as a queued job and enqueues the next
- All transitions are persisted in the database

## Project Structure

```
app/AsyncOrchestration/
├── API/
├── Application/
├── Domain/
├── Infrastructure/
└── Ops/

config/

database/migrations/

specs/
```

## Requirements

- PHP + Laravel
- Queue backend (Redis recommended)
- Relational database (PostgreSQL or MySQL)

## How to Run (Local)

### Infrastructure (DB + Redis)

```bash
docker compose up -d
```

Default credentials (PostgreSQL):

- DB: `async_orch`
- User: `async_orch`
- Password: `async_orch`
 - Port: `5433`

Redis is exposed on host port `6380`.

> App bootstrap and queue worker commands will be added once the Laravel setup is in place.

### App Bootstrap

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Queue worker:

```bash
php artisan queue:work
```

## API (MVP)

- `POST /api/workflows/{type}/start`
- `GET /api/workflows/runs/{runId}`
- `POST /api/workflows/runs/{runId}/pause`
- `POST /api/workflows/runs/{runId}/resume`
- `POST /api/workflows/runs/{runId}/cancel`
- `POST /api/workflows/runs/{runId}/replay`

## CLI

- `php artisan workflow:retry-step {run_id} {step_index}`

## Documentation

- Spec and plan: `specs/001-orquestrador-assincrono/`
- Manual tests: `docs/manual-tests.md`

## Status

WIP. Foundation setup and specs are in place; implementation is in progress.
