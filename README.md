# PBT DetectorGUI

A local detector configuration interface using HTML, CSS, JavaScript, JSON, and
PHP. The UI reuses the existing PBT Replay GUI styles. No JSX, Python application
files, frontend framework, npm installation, or build step is required.

## Run

For Linux sudo testing, double-click `start-wsl.cmd`. It starts Ubuntu and the
Linux PHP service, then automatically opens <http://127.0.0.1:8766>.
See `docs/linux-development.md` for Chrome, VS Code, and the exact sudo permission.

For the Windows PHP service, double-click `start.cmd`. It automatically opens
<http://127.0.0.1:8765> once the service is ready.
Both launchers prefer Chrome and otherwise use the default browser. Clicking
again opens the existing service without starting another copy. Do not open
`public/index.html` directly; an HTML file cannot start the PHP service.
Keep the server running; use Ctrl+C in its terminal to stop it.
The launcher uses PHP on PATH, or the existing PHP runtime at
`../Workplace/.tools/php-8.4/php.exe`. That runtime is not modified.

On another computer, install PHP 8.2 or later and run from this directory:

```sh
php -S 127.0.0.1:8765 -t public public/router.php
```

Use the router and the `public` web root. Do not open the HTML as a local file or
expose this local development server to a network.

## Current behaviour

- Selectors start at `select a number`; integer fields start empty. `.json file`
  is a placeholder, not a saved value. Reload starts a new, empty form.
- Setup and Acquire validate every field. Fields also validate on blur; after
  validation, corrections update their hints while typing.
- Invalid input receives a red border and red text in the existing hint position.
  Valid corrections restore the grey hint. Field errors never enter the terminal
  and never trigger its red exclamation mark.
- Setup is always blue. It validates settings, generates the filename, saves the
  latest parameters to `runtime/configurations/parameters.json`, then waits at
  least one second before enabling acquisition. Hardware preparation is not yet
  connected; the one-second delay is the requested temporary stand-in.
- Acquire is grey until preparation finishes, then green. A grey-button click
  reports that Setup is required without sending an acquisition request. The
  button uses `aria-disabled` with a guarded click handler so the requested error
  feedback remains available. Changing detector settings makes it grey again.
  Renaming Filename alone does not invalidate prepared detector settings.
- PHP independently checks the preparation delay and the exact prepared settings.
  Another browser session cannot reuse this session's preparation.
- The current default is explicitly labelled **preview mode**. After Setup,
  Acquire opens the waiting animation and elapsed timer; Stop returns to the form.
  No detector commands, measurements, output files, or artificial signals are
  generated. The elapsed timer is a workflow preview, not proof of hardware activity.
- Set `DETECTOR_MODE=hardware` on the server to disable the preview. Until a real
  adapter exists, hardware acquisition returns HTTP 501 and does not start. The
  real adapter will need start/stop acknowledgements and an ISO `started_at` time.
- The bottom Python service tab expands a log terminal. Acquisition/transport and
  sudo failures appear in red. When collapsed, `!` indicates an unread service
  error. Opening the terminal acknowledges existing errors; closing it keeps the
  indicator clear until another error arrives. Errors received while the terminal
  is open are already visible and do not create an unread indicator. The red log
  entries remain available until reload.
- `Check sudo` in the expanded terminal runs only a fixed identity probe. See
  `docs/sudo.md`. PHP runs the backend; Python is not connected yet. The tab name
  follows the approved design. The terminal is not an arbitrary shell.

Browse records a selected file's **name only**. It does not upload JSON contents,
discover an absolute local path, or validate file existence/content on the detector
host. Those checks require the hardware file contract. Typed references must end
in `.json`. No extension is appended to Filename.

Filename starts blank below Setup/Acquire. Each valid Setup click generates
`mmHHddMM_boards{nboards}_integrationt{integration_time}_scale{full_scale_range}`
using Europe/London time (including summer time). For example, 18:45 on 29 October
with 4 boards, integration time 1000, and scale 12.5 produces
`45182910_boards4_integrationt1000_scale12.5`. It remains editable. A subsequent
Setup generates it again. Its minute-level precision can produce duplicates;
future output-file writing must handle collisions without silent replacement.

Configuration history and import/export are not implemented. Enter settings and
select calibration/background file references manually.

## Files

| File or directory | Responsibility |
| --- | --- |
| `public/index.html` | Page structure and bottom terminal |
| `scripts/open-browser.ps1` | Waits for the local service and opens the browser |
| `public/app.js` | Form interactions, inline errors, API calls, terminal, monitor |
| `public/validation.js` | Browser-side type and interval validation |
| `public/workflow.js` | Filename format and preparation message |
| `public/vendor/` | Used styles retained from the existing PBT GUI |
| `public/styles.css` | Approved form, terminal, and responsive overrides |
| `parameters.schema.json` | Shared labels, ranges, types, and empty defaults |
| `PARAMETERS.md` | Parameter variable mapping |
| `backend/parameters.php` | Server validation and atomic JSON persistence |
| `backend/detector.php` | Future detector/Python service integration boundary |
| `backend/privileged.php` | Fixed Linux sudo command, disabled by default |
| `backend/api.php` | API, session token, and storage |
| `public/router.php` | Local origin checks and asset allowlist |
| `runtime/` | Ignored configuration/session storage outside the web root |

## Roadmap

1. Specify parameter units, calibration file semantics, detector setup/start/stop,
   transport, acknowledgements, timeouts, and recovery.
2. Implement the hardware adapter or a PHP-to-Python service client.
3. Add measured sample rate (including the proposed 5 kSPS target), data transport,
   elapsed-time recovery, and connection status.
4. Add frame-by-frame curves in this same workspace, using the Replay GUI as the
   reference after confirming axes, units, frame structure, and retention.
5. Test real hardware disconnects, cancellation, long runs, and malformed output.
