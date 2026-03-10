Dev Service
===========

Purpose
-------
This simple development service helps start and monitor Chamy local services (PHP built-in server, later FTP, APIs, etc.). It writes a small status JSON and appends logs that can be consumed by `scripts/terminal_panel.php` to display a compact terminal overview.

Files
-----
- `scripts/dev_service.php` — main PHP monitor script (supports `run`, `start` via wrapper, `stop`, `status`).
- `scripts/dev_service.ps1` — Windows PowerShell helper to run the service as a background job.
- `scripts/terminal_status.json` — status file (read by the terminal panel).
- `scripts/terminal.log` — example logfile used by terminal panel.
- `scripts/terminal_panel.php` — CLI panel viewer (reads `terminal_status.json` and `terminal.log`).

Usage
-----
Run in foreground (recommended for debugging):

```powershell
php scripts/dev_service.php run
```

Start as background job on Windows (PowerShell):

```powershell
.\\scripts\\dev_service.ps1 -Action start
```

Stop background jobs:

```powershell
.\\scripts\\dev_service.ps1 -Action stop
```

Check status (JSON):

```powershell
php scripts/dev_service.php status
```

View the terminal panel (separate terminal):

```powershell
php scripts/terminal_panel.php
```

Notes
-----
- The script by default runs `php -S localhost:8080 -t public` — pass a different server command as 2nd argument.
- On Windows, the PowerShell job wrapper starts the PHP monitor in the background as a job; the job's output isn't streamed to the console — view logs in `scripts/terminal.log` or the status JSON.
- The service performs a simple HTTP check against `http://localhost:8080`. Extensions (FTP, modules healthchecks) can be added in `dev_service.php`.
