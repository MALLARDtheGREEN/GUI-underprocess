const integerPattern = /^[+-]?\d+$/;

export function validateParameters(raw, schema) {
  const errors = {};
  const values = {};
  for (const [name, rule] of Object.entries(schema)) {
    const value = raw[name];
    const text = typeof value === 'string' ? value.trim() : value;
    if (rule.kind === 'integer' || rule.kind === 'enum') {
      const isNumber = typeof value === 'number' && Number.isFinite(value);
      const isNumericString = typeof text === 'string' && /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(text);
      if (!isNumber && !isNumericString) {
        errors[name] = `Wrong type: The input should be ${rule.kind === 'integer' ? 'an integer' : 'a number'}.`;
        continue;
      }
      const parsed = Number(text);
      if (rule.kind === 'integer') {
        if (!Number.isSafeInteger(parsed) || (typeof text === 'string' && !integerPattern.test(text))) {
          errors[name] = 'Wrong type: The input should be an integer.';
        } else if (parsed < rule.min || parsed > rule.max) {
          errors[name] = `Wrong interval: The input should between ${rule.min} and ${rule.max}.`;
        }
      } else if (!rule.values.includes(parsed)) {
        errors[name] = `Wrong interval: The input should be one of ${rule.values.join(', ')}.`;
      }
      values[name] = parsed;
    } else {
      if (typeof value !== 'string' || !text || text.includes('\0')) {
        errors[name] = 'Wrong type: The input should be a non-empty string.';
      } else if (rule.kind === 'json_file' && !/[^/\\]+\.json$/i.test(text)) {
        errors[name] = 'Wrong type: The input should be a string ending in .json.';
      }
      values[name] = text;
    }
  }
  return { errors, values };
}
