# Printly reference print agent

A minimal local service that connects a store's physical printers to Printly's
auto-print bridge. It polls the cloud for print jobs, downloads each file,
prints it, and reports status back — the same contract a production in-store
agent implements (see `planning/01-data-model-and-architecture.md` §5.3).

This is a **reference / test** implementation: zero dependencies, ~150 lines,
Node 18+. Use it to exercise the pipeline end-to-end and as a spec for a real
agent (packaged service, printer discovery, retries, auto-update).

## How it fits

```
paid order ──▶ Printly creates print_jobs ──▶ this agent polls /api/agent/jobs
                                                     │ downloads file, prints
                                                     ▼ PATCH status (printing→done)
                                    order advances accepted → in_progress → ready
```

## Setup

1. In the app, go to **Printers → Add agent**, copy the token shown once.
   (The demo store already has one — token in `DemoStoreSeeder::DEMO_AGENT_TOKEN`.)
2. Configure and run:

```bash
cd tools/print-agent
cp .env.example .env      # paste PRINTLY_AGENT_TOKEN
node index.mjs
```

Requires the API running (`php artisan serve`) and a queue worker
(`php artisan queue:work`) so uploaded files get analysed and jobs are created.

## Modes

- `PRINT_MODE=virtual` (default) — saves the file to `./spool` and logs a
  "printed" line. No physical printer needed; ideal for testing.
- `PRINT_MODE=lp` — pipes the file to the CUPS `lp` command (macOS/Linux) with
  `-n <copies>`. On Windows, adapt `printFile()` to your print tooling.

## The agent API (for building a real agent)

All requests send `Authorization: Bearer <token>`.

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/agent/me` | Bootstrap: agent name + its printers. |
| GET | `/api/agent/jobs` | Claim queued jobs (marks them `sent`); returns file metadata + `download_url`. |
| GET | `/api/agent/jobs/{id}/file` | Stream the file to print. |
| PATCH | `/api/agent/jobs/{id}` | Report `{ "status": "printing" \| "done" \| "error", "error": "…" }`. |

The heartbeat (`last_seen_at`) is updated automatically on every authenticated
request. A failed job can be re-queued by the owner from the Printers page.
