<?php
declare(strict_types=1);

function parameter_schema(): array {
    return json_decode(file_get_contents(dirname(__DIR__) . '/parameters.schema.json'), true, 512, JSON_THROW_ON_ERROR);
}

function validate_parameters(mixed $raw): array {
    $errors = [];
    $values = [];
    if (!is_array($raw) || array_is_list($raw)) {
        return ['errors' => ['_form' => 'Wrong type: The request should be a JSON object.'], 'values' => []];
    }
    $schema = parameter_schema();
    if (array_diff(array_keys($raw), array_keys($schema))) {
        $errors['_form'] = 'Unknown parameters are not accepted.';
    }
    foreach ($schema as $name => $rule) {
        $value = $raw[$name] ?? null;
        if (in_array($rule['kind'], ['integer', 'enum'], true)) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value)) {
                $expected = $rule['kind'] === 'integer' ? 'an integer' : 'a number';
                $errors[$name] = "Wrong type: The input should be $expected.";
            } elseif ($rule['kind'] === 'integer') {
                if (floor($value) != $value || abs($value) > 9007199254740991) {
                    $errors[$name] = 'Wrong type: The input should be an integer.';
                } elseif ($value < $rule['min'] || $value > $rule['max']) {
                    $errors[$name] = "Wrong interval: The input should between {$rule['min']} and {$rule['max']}.";
                } else {
                    $values[$name] = (int)$value;
                }
            } elseif (!in_array($value, $rule['values'])) {
                $errors[$name] = 'Wrong interval: The input should be one of ' . implode(', ', $rule['values']) . '.';
            } else {
                $values[$name] = $value;
            }
        } elseif (!is_string($value) || trim($value) === '' || str_contains($value, "\0")) {
            $errors[$name] = 'Wrong type: The input should be a non-empty string.';
        } elseif ($rule['kind'] === 'json_file' && !preg_match('~[^/\\\\]+\.json$~iD', trim($value))) {
            $errors[$name] = 'Wrong type: The input should be a string ending in .json.';
        } else {
            $values[$name] = trim($value);
        }
    }
    return ['errors' => $errors, 'values' => $values];
}

function save_parameters(array $parameters, string $directory): void {
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create the configuration directory.');
    }
    $temporary = tempnam($directory, '.config-');
    if ($temporary === false) throw new RuntimeException('Cannot create a configuration file.');
    try {
        $json = json_encode($parameters, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $directory . '/parameters.json')) {
            throw new RuntimeException('Cannot save the configuration.');
        }
    } finally {
        if (is_file($temporary)) unlink($temporary);
    }
}
