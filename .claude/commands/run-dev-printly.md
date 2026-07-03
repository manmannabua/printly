# Run Dev Printly

Free ports 8000, 5173, 8080 (only those — never blanket-kill `node.exe` / `php.exe`, the user may have other processes running) and start the four Printly dev servers:

| Port | Service | Command | Cwd |
|------|---------|---------|-----|
| 8000 | Laravel backend API | `php artisan serve --port=8000` | `D:/dev/printly/backend` |
| 5173 | frontend-v2 Vite SPA | `npx vite dev` | `D:/dev/printly/frontend-v2` |
| 8080 | Reverb WebSocket server | `php artisan reverb:start --port=8080` | `D:/dev/printly/backend` |
| — | Queue worker (file analysis + broadcasts) | `php artisan queue:work` | `D:/dev/printly/backend` |

## Instructions

### 1. Free the three dev ports
Kill only the processes bound to ports 8000, 5173, 8080 — leave any unrelated processes alone:

```
netstat -ano | awk '/LISTENING/ && $2 ~ /:(8000|5173|8080)$/ {print $5}' | sort -u | xargs -r -I{} taskkill //F //PID {} 2>&1
```

Empty output or `ERROR: ... could not be terminated` are both fine — they just mean nothing was bound.

### 2. Start the servers
Launch all four via Bash with `run_in_background: true` in a single message:

- `cd /d/dev/printly/backend && php artisan serve --port=8000`
- `cd /d/dev/printly/frontend-v2 && npx vite dev`
- `cd /d/dev/printly/backend && php artisan reverb:start --port=8080`
- `cd /d/dev/printly/backend && php artisan queue:work`

### 3. Verify each bound to the expected port
Wait briefly, then confirm:

- Laravel logs `Server running on [http://127.0.0.1:8000]`
- frontend-v2 Vite logs `Local: http://localhost:5173/`
- Reverb logs `Starting server on 0.0.0.0:8080` — check via `netstat -ano | Select-String ":8080"`
- Queue worker logs `[YYYY-MM-DD HH:MM:SS] Processing jobs from the [default] queue.`

The SPA's `vite.config.ts` sets `port: 5173` + `strictPort: true`, so it **fails loudly** if 5173 is taken rather than drifting to 5175. A 5175 fallback would break admin login — 5173 is the only dev port in the backend's CORS / `SANCTUM_STATEFUL_DOMAINS` allow-list, so **never edit `.env` to chase a port; free 5173 and restart instead.**

### 4. Report
Show the user a short summary listing the three URLs plus queue worker status. The storefront is at `http://localhost:5173/s/campus-print-hub`; seeded logins are in the README (owner@printly.test / password).

## Notes
- Do **not** start the backend's `npm run dev`. `backend/resources/js` + `backend/vite.config.js` are vestigial leftovers from the HRIS clone; the real SPA lives entirely in `frontend-v2/`. Starting it would grab 5173 and force the SPA to a non-whitelisted fallback port.
- The **queue worker is required** for the file-based flow: uploaded files are analysed (page count / paper size → pricing) by a queued job, and auto-print jobs + notifications broadcast through the queue. Without it, uploads stay `analysis_status: pending` and real-time updates are silently dropped.
- **Reverb** (8080) powers live updates on the queue board, order tracking, chat, and the printers page. Without it those features fall back to polling — but note `frontend-v2/.env` ships with `VITE_REVERB_APP_KEY` blank, so the SPA polls by default anyway. To exercise websockets, set that key to the backend's `REVERB_APP_KEY` before starting.
- This is a pure server-start: do **not** run `npm install`, `composer install`, or `php artisan migrate` as part of this command.
- If a seed/reset is needed, that's a separate step: `cd backend && php artisan migrate:fresh --seed` (creates the demo store, catalog, and an auto-print agent — see `DemoStoreSeeder`).
