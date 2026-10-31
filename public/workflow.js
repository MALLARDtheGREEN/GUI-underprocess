export const NOT_READY_MESSAGE = 'Detectors are not ready. Configure the parameters and click Setup first.';

// The requested order is minute, hour, day, month, using laboratory local time.
export function makeFilename(parameters, date = new Date()) {
  const parts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Europe/London', minute: '2-digit', hour: '2-digit',
    day: '2-digit', month: '2-digit', hourCycle: 'h23',
  }).formatToParts(date).map(part => [part.type, part.value]));
  return `${parts.minute}${parts.hour}${parts.day}${parts.month}_boards${parameters.nboards}_integrationt${parameters.integration_time}_scale${parameters.full_scale_range}`;
}
