# Privileged operation boundary

The backend is PHP. A future Python service client belongs in
`backend/detector.php`, after its protocol and acknowledgement behaviour are known.
No Python script or dependency environment is required here.

The terminal's Check sudo button sends only `{"action":"identity"}` to
`/api/system/sudo`. The server owns the complete argument array:

```text
/usr/bin/sudo -n -- /usr/bin/id -u
```

`proc_open` receives an array and bypasses the shell. The Linux-only probe has a
five-second timeout, does not collect passwords, and is disabled unless the server
environment contains `DETECTOR_ENABLE_SUDO=1`. Extra command fields and unknown
actions are rejected. Windows reports that Linux is required.

For Linux, an administrator can review an exact rule using `visudo`, substituting
the real unprivileged service account for `detectorgui`:

```sudoers
detectorgui ALL=(root) NOPASSWD: /usr/bin/id -u
```

This example permits only the identity probe. Actual hardware operations should
use a fixed root-owned helper with narrow operations and validated data. Do not
grant unrestricted `sudo python`, arbitrary shell execution, or root privileges
to the whole web server. The application itself does not change sudoers or run detector
commands. The current workstation's WSL setup is documented in
`linux-development.md`; it installs only the identity-probe permission for `venus`.
Other machines still require their own reviewed policy and integration checks.
