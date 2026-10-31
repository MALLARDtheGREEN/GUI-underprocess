# Parameter variables

These names are identical in JavaScript payloads, PHP associative arrays, and
saved JSON. The shared rules are in `parameters.schema.json`.

| UI parameter | Variable name | Type and allowed values |
| --- | --- | --- |
| Vbus | `vbus` | Number: 0 or 1 |
| Enable | `enable` | Number: 0 or 1 |
| nboards | `nboards` | Integer: 1–4 inclusive |
| Integration time | `integration_time` | Integer: 200–1000 inclusive |
| Number of module | `number_of_modules` | Integer: 1–5 inclusive |
| Calibration file | `calibration_file` | Nonempty string ending in `.json`, case-insensitive |
| Background file | `background_file` | Nonempty string ending in `.json`, case-insensitive |
| Full scale range | `full_scale_range` | Number: 12.5, 50, 100, 150, 200, 250, 300, 350 |
| Filename | `filename` | Starts blank; generated at Setup, then editable; must be a nonempty string for submission |

Full scale range is a numeric enumeration, not an integer, because 12.5 is required.
Units for integration time and full scale range remain unspecified.
Integer text fields accept plain integer notation. `1.0`, `1.5`, `2e2`, blank
strings, and text produce type errors. API JSON must use numbers, not strings.

Access each variable independently:

```js
const { vbus, enable, nboards, integration_time, number_of_modules,
  calibration_file, background_file, full_scale_range, filename } = parameters;
```

```php
$vbus = $parameters['vbus'];
$enable = $parameters['enable'];
$nboards = $parameters['nboards'];
$integration_time = $parameters['integration_time'];
$number_of_modules = $parameters['number_of_modules'];
$calibration_file = $parameters['calibration_file'];
$background_file = $parameters['background_file'];
$full_scale_range = $parameters['full_scale_range'];
$filename = $parameters['filename'];
```

Values are configuration data, never executable code. This version does not use
Filename as a server path or use parameter values as shell arguments.

The generated format is `mmHHddMM_boards{nboards}_integrationt{integration_time}_scale{full_scale_range}`.
Time is captured at Setup using Europe/London local time. Example:
`45182910_boards4_integrationt1000_scale12.5`. Filename is below the action buttons.
