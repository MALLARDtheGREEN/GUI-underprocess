<?php
declare(strict_types=1);

/** Execute only a fixed Linux identity probe. No browser-supplied command text. */
function sudo_identity(): array {
    if (PHP_OS_FAMILY !== 'Linux') {
        return ['available' => false, 'message' => 'Sudo requires Linux. This PHP server is running on ' . PHP_OS_FAMILY . '.'];
    }
    if (getenv('DETECTOR_ENABLE_SUDO') !== '1') {
        return ['available' => false, 'message' => 'Sudo is disabled. Configure the exact Linux command and set DETECTOR_ENABLE_SUDO=1 to enable it.'];
    }
    if (!is_executable('/usr/bin/sudo') || !is_executable('/usr/bin/id')) {
        return ['available' => false, 'message' => 'The approved executable is missing.'];
    }
    // Array form bypasses the shell. The command and all arguments are fixed.
    $process = proc_open(['/usr/bin/sudo', '-n', '--', '/usr/bin/id', '-u'],
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes, null, ['PATH' => '/usr/bin:/bin', 'LANG' => 'C']);
    if (!is_resource($process)) return ['available' => false, 'message' => 'Cannot start the approved command.'];
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $deadline = microtime(true) + 5;
    $output = '';
    $timedOut = false;
    do {
        $output .= stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        $state = proc_get_status($process);
        if (!$state['running']) break;
        if (microtime(true) >= $deadline) {
            $timedOut = true;
            proc_terminate($process, 9);
            break;
        }
        usleep(20000);
    } while (true);
    $output .= stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $closed = proc_close($process);
    $exit = $state['exitcode'] >= 0 ? $state['exitcode'] : $closed;
    if ($timedOut) return ['available' => false, 'message' => 'The approved command timed out after 5 seconds.'];
    if ($exit !== 0 || trim($output) !== '0') {
        return ['available' => false, 'message' => 'Sudo was denied. Configure the exact NOPASSWD command for the service user. No password is collected by this app.'];
    }
    return ['available' => true, 'message' => 'Sudo identity check succeeded (uid 0). No detector command was executed.'];
}
