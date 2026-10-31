# Linux development on this workstation

The existing `Ubuntu` WSL 2 distribution runs Ubuntu 24.04. The normal Linux user
is `venus`. PHP CLI is installed through Ubuntu's package manager. The application
remains in `D:\lab-workspace\DetectorGUI`, mounted inside Linux as
`/mnt/d/lab-workspace/DetectorGUI`. No second copy of the source is needed.

## Run from Windows and Chrome

Double-click `start-wsl.cmd`. It starts the Linux service and automatically opens
<http://127.0.0.1:8766> in Chrome, or the default browser if Chrome is unavailable.
The launcher waits for the service to be ready, including a cold Ubuntu start.
If the service is already running, it opens that instance without starting another.
Keep the launcher running; Ctrl+C stops the Linux server. Port 8766 distinguishes
this service from the existing Windows PHP service on port 8765.

Expand **Python service** and click **Check sudo**. A successful Linux check prints:

```text
Sudo identity check succeeded (uid 0). No detector command was executed.
```

The web server runs as `venus`, not root. The tab name remains Python service from
the approved UI; the current backend is PHP and detector acquisition is a preview.
Chrome is the client: Linux PHP executes sudo on the server.

## Open the Linux terminal

Open PowerShell or Windows Terminal and run:

```powershell
wsl -d Ubuntu
```

This opens the installed Ubuntu Linux shell as `venus`. Go to the project with:

```sh
cd /mnt/d/lab-workspace/DetectorGUI
```

Run `exit` to return to Windows. This is a WSL command-line environment; no separate
Linux desktop was installed. The website launcher starts Linux automatically, so
opening this terminal first is optional.

## Run from VS Code

The Microsoft WSL extension (`ms-vscode-remote.remote-wsl`) is installed. Open this
folder in the Ubuntu remote environment from a Windows terminal:

```powershell
code --remote wsl+Ubuntu /mnt/d/lab-workspace/DetectorGUI
```

Check that the lower-left remote indicator says **WSL: Ubuntu**. Use
**Terminal > Run Task > DetectorGUI: Start Linux PHP**, or run:

```sh
bash scripts/start-linux.sh
```

The optional **DetectorGUI: Check Linux sudo** task tests the same command directly
in the Linux terminal. Do not start a second server on 8766 while one is running.
The VS Code terminal shows PHP request logs and the browser terminal shows service diagnostics; PHP
breakpoint debugging would additionally need Xdebug and is not configured here.

## Exact sudo permission

This workstation has a root-owned, mode-0440 rule at
`/etc/sudoers.d/detector-gui-check`:

```sudoers
venus ALL=(root) NOPASSWD: /usr/bin/id -u
```

Only this exact non-interactive identity command is permitted without a password
by this rule. The existing user's other sudo rules are unchanged. There is no
permission for arbitrary browser commands, shells, Python, or detector hardware.
The rule is validated with `visudo`. Review it with:

```sh
sudo -l
sudo -n -- /usr/bin/id -u
```

The Linux launcher enables `DETECTOR_ENABLE_SUDO=1` for this process and uses
`runtime/linux/` for its local session/configuration files. Normal Windows startup
and its runtime files are unchanged. All listeners bind to loopback; no firewall
opening or network exposure is configured.

To disable the probe, start PHP without `DETECTOR_ENABLE_SUDO=1`; to remove the
machine permission, an administrator can remove only the named sudoers drop-in
and validate the remaining policy with `visudo -c`. Do not remove other rules.

References: [VS Code WSL development](https://code.visualstudio.com/docs/remote/wsl)
and [Windows access to WSL network applications](https://learn.microsoft.com/en-us/windows/wsl/networking).
