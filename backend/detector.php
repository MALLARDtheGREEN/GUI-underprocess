<?php
declare(strict_types=1);

/**
 * Hardware integration boundary. Implement a fixed local command or a Python
 * service client here once its protocol, acknowledgement, and stop behaviour exist.
 * Browser configuration never becomes executable code or shell arguments.
 */
function detector_mode(): string {
    return getenv('DETECTOR_MODE') === 'hardware' ? 'hardware' : 'preview';
}

function detector_signature(array $parameters): string {
    // Renaming the output does not change detector settings.
    unset($parameters['filename']);
    return hash('sha256', json_encode($parameters, JSON_THROW_ON_ERROR));
}

function acquire_detector(array $parameters): array {
    if (detector_mode() === 'preview') {
        return ['started' => true, 'mode' => 'preview', 'started_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'message' => 'Preview acquisition started. No detector is connected and no data is generated.'];
    }
    return [
        'started' => false,
        'message' => 'Parameters are valid. Acquisition was not started: no detector interface is configured.',
    ];
}
