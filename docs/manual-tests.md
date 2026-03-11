# Manual Test Scenarios

## Infra

```bash
docker compose up -d
```

## Database and migrations

```bash
php artisan migrate
```

## Flows

### Start and status

1. `POST /api/workflows/import_users/start` with minimal payload
2. `GET /api/workflows/runs/{runId}` to monitor

### Failure and retry

1. Force an exception in a step
2. Verify `next_retry_at` and reprocessing

### Pause/Resume/Cancel

1. `POST /api/workflows/runs/{runId}/pause`
2. `POST /api/workflows/runs/{runId}/resume`
3. `POST /api/workflows/runs/{runId}/cancel`

### Replay

1. Ensure run is in FAILED/DEAD
2. `POST /api/workflows/runs/{runId}/replay` with `step_index`

### Tenant rate limit

1. Start runs with `tenant_id`
2. Exceed configured limit
3. Verify backoff and reprocessing
