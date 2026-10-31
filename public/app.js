import { validateParameters } from './validation.js';
import { makeFilename, NOT_READY_MESSAGE } from './workflow.js';

const form = document.querySelector('#parameter-form');
const fields = document.querySelector('#parameter-fields');
const formStatus = document.querySelector('#form-status');
const terminalToggle = document.querySelector('#terminal-toggle');
const terminalBody = document.querySelector('#terminal-body');
const terminalOutput = document.querySelector('#terminal-output');
const errorIndicator = document.querySelector('#error-indicator');
const sudoButton = document.querySelector('#sudo-check');
const acquireButton = document.querySelector('.acquire-button');
const controls = new Map();
const touched = new Set();
let schema;
let token;
let submitted = false;
let hasUnreadServiceError = false;
let timer;
let busy = false;
let preparation = 'idle';

function updateAcquire() {
  const ready = preparation === 'ready' && !busy;
  acquireButton.classList.toggle('ready', ready);
  acquireButton.setAttribute('aria-disabled', String(!ready));
}

function invalidatePreparation(name) {
  if (name !== 'filename') {
    preparation = 'idle';
    updateAcquire();
  }
}

function setStatus(message = '', error = false) {
  formStatus.textContent = message;
  formStatus.classList.toggle('error', error);
}

function updateErrorIndicator() {
  errorIndicator.hidden = !hasUnreadServiceError || terminalToggle.getAttribute('aria-expanded') === 'true';
}

// Field-validation errors must never enter this log or set the service indicator.
function logService(message, error = false) {
  const line = document.createElement('div');
  line.textContent = `[${error ? 'ERROR' : 'INFO'}] ${message}`;
  if (error) {
    line.className = 'log-error';
    hasUnreadServiceError = terminalToggle.getAttribute('aria-expanded') !== 'true';
  }
  terminalOutput.append(line);
  if (terminalOutput.childElementCount > 200) terminalOutput.firstElementChild.remove();
  terminalBody.scrollTop = terminalBody.scrollHeight;
  updateErrorIndicator();
}

terminalToggle.addEventListener('click', () => {
  const open = terminalToggle.getAttribute('aria-expanded') !== 'true';
  if (open) hasUnreadServiceError = false;
  terminalToggle.setAttribute('aria-expanded', String(open));
  document.querySelector('#terminal-arrow').textContent = open ? '▾' : '▸';
  terminalBody.hidden = !open;
  sudoButton.hidden = !open;
  document.querySelector('.detector-app').classList.toggle('terminal-open', open);
  updateErrorIndicator();
});

async function api(path, body) {
  const response = await fetch(`/api/${path}`, {
    method: body === undefined ? 'GET' : 'POST',
    headers: body === undefined ? {} : { 'Content-Type': 'application/json', 'X-Detector-Token': token },
    body: body === undefined ? undefined : JSON.stringify(body),
    signal: AbortSignal.timeout(15000),
  });
  let result;
  try { result = await response.json(); }
  catch { throw new Error('The service returned an invalid response.'); }
  if (!response.ok) {
    const error = new Error(result.message || 'The service could not complete this request.');
    error.fields = result.errors;
    throw error;
  }
  return result;
}

function readParameters() {
  return Object.fromEntries([...controls].map(([name, control]) => [name, control.input.value]));
}

function showFieldError(name, error = '') {
  const control = controls.get(name);
  if (!control) return;
  control.input.setAttribute('aria-invalid', String(Boolean(error)));
  control.message.textContent = error || schema[name].hint;
  control.message.classList.toggle('error', Boolean(error));
}

function validateField(name) {
  showFieldError(name, validateParameters(readParameters(), schema).errors[name]);
}

function makeFields() {
  for (const [name, rule] of Object.entries(schema)) {
    const row = document.createElement('div');
    row.className = `parameter-row${rule.kind === 'json_file' ? ' file-row' : ''}`;
    const label = document.createElement('label');
    label.htmlFor = name;
    label.textContent = rule.label;
    const wrapper = document.createElement('div');
    wrapper.className = 'field-control';
    const input = document.createElement(rule.kind === 'enum' ? 'select' : 'input');
    input.id = name;
    input.name = name;
    input.setAttribute('aria-describedby', `${name}-message`);
    input.setAttribute('aria-invalid', 'false');
    if (rule.kind === 'enum') {
      input.add(new Option('select a number', ''));
      for (const value of rule.values) input.add(new Option(String(value), String(value)));
    } else {
      input.type = 'text';
      input.autocomplete = 'off';
      input.spellcheck = false;
      if (rule.kind === 'integer') input.inputMode = 'numeric';
      if (rule.placeholder) input.placeholder = rule.placeholder;
    }
    input.value = String(rule.default);
    const message = document.createElement('span');
    message.id = `${name}-message`;
    message.className = 'field-message';
    message.setAttribute('aria-live', 'polite');
    message.textContent = rule.hint;
    controls.set(name, { input, message });
    input.addEventListener('blur', () => { touched.add(name); validateField(name); });
    input.addEventListener('input', () => {
      invalidatePreparation(name);
      setStatus();
      if (touched.has(name) || submitted) validateField(name);
    });
    input.addEventListener('change', () => { invalidatePreparation(name); touched.add(name); validateField(name); });
    wrapper.append(input);
    if (rule.kind === 'json_file') {
      const picker = document.createElement('input');
      picker.type = 'file';
      picker.accept = '.json,application/json';
      picker.hidden = true;
      picker.tabIndex = -1;
      const browse = document.createElement('button');
      browse.type = 'button';
      browse.className = 'browse';
      browse.textContent = 'Browse...';
      browse.setAttribute('aria-label', `Browse ${rule.label.toLowerCase()}`);
      browse.title = 'Select a file name. File contents are not uploaded.';
      browse.addEventListener('click', () => picker.click());
      picker.addEventListener('change', () => {
        if (picker.files[0]) {
          input.value = picker.files[0].name;
          invalidatePreparation(name);
          touched.add(name);
          validateField(name);
          setStatus();
        }
        picker.value = '';
      });
      wrapper.append(picker, browse);
    }
    row.append(label, wrapper, message);
    (name === 'filename' ? document.querySelector('#filename-field') : fields).append(row);
  }
}

function setBusy(value) {
  busy = value;
  for (const element of form.querySelectorAll('input, select, button')) {
    if (element !== acquireButton) element.disabled = value;
  }
  updateAcquire();
}

function enterMonitor(startedAt, preview) {
  document.querySelector('#control-page').hidden = true;
  document.querySelector('#monitor-page').hidden = false;
  document.querySelector('#monitor-detail').textContent = preview
    ? 'Preview mode: no detector is connected and no data is generated.'
    : 'Acquisition started. Waiting for detector output.';
  clearInterval(timer);
  const receivedAt = performance.now();
  const initialElapsed = Math.max(0, Date.now() - Date.parse(startedAt));
  function tick() {
    const seconds = Math.floor((initialElapsed + performance.now() - receivedAt) / 1000);
    const parts = [Math.floor(seconds / 3600), Math.floor(seconds / 60) % 60, seconds % 60];
    document.querySelector('#elapsed-time').value = parts.map(value => String(value).padStart(2, '0')).join(':');
  }
  tick();
  timer = setInterval(tick, 250);
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  const action = event.submitter?.value || 'setup';
  if (action === 'acquire' && (preparation !== 'ready' || busy)) {
    logService(NOT_READY_MESSAGE, true);
    setStatus(NOT_READY_MESSAGE, true);
    return;
  }
  if (busy || !schema) return;
  if (action === 'setup') invalidatePreparation('setup');
  submitted = true;
  const raw = readParameters();
  // Setup generates the output name after validating the detector settings.
  if (action === 'setup') raw.filename = 'pending_setup';
  const { errors, values } = validateParameters(raw, schema);
  for (const name of controls.keys()) showFieldError(name, errors[name]);
  if (Object.keys(errors).length) {
    setStatus();
    controls.get(Object.keys(errors)[0]).input.focus();
    return;
  }
  if (action === 'setup') {
    values.filename = makeFilename(values);
    controls.get('filename').input.value = values.filename;
    preparation = 'preparing';
  }
  setBusy(true);
  setStatus(action === 'setup' ? 'Preparing configuration…' : 'Requesting acquisition…');
  try {
    const result = await api(action, values);
    if (action === 'acquire') {
      if (result.started !== true || !Number.isFinite(Date.parse(result.started_at))) {
        throw new Error('The detector did not return a valid acquisition acknowledgement.');
      }
      logService(result.mode === 'preview' ? result.message : 'Detector acknowledged acquisition start.');
      enterMonitor(result.started_at, result.mode === 'preview');
    } else {
      await new Promise(resolve => setTimeout(resolve, Math.max(1000, Number(result.ready_in_ms) || 1000)));
      preparation = 'ready';
    }
    setStatus(result.message);
  } catch (error) {
    if (action === 'setup') preparation = 'idle';
    if (error.fields) {
      for (const name of controls.keys()) showFieldError(name, error.fields[name]);
      setStatus(error.fields._form || '', true);
      const first = Object.keys(error.fields).find(name => controls.has(name));
      if (first) controls.get(first).input.focus();
    } else if (action === 'acquire') {
      logService(error.message, true);
      setStatus('Acquisition was not started. See Python service for details.', true);
    } else {
      setStatus(error.message, true);
    }
  } finally { setBusy(false); }
});

document.querySelector('#stop-acquisition').addEventListener('click', async event => {
  const button = event.currentTarget;
  button.disabled = true;
  try {
    const result = await api('stop', {});
    if (result.stopped !== true) throw new Error('The detector did not acknowledge stopping.');
    clearInterval(timer);
    document.querySelector('#monitor-page').hidden = true;
    document.querySelector('#control-page').hidden = false;
    logService(result.mode === 'preview' ? result.message : 'Detector acknowledged acquisition stop.');
  } catch (error) { logService(error.message, true); }
  finally { button.disabled = false; }
});

sudoButton.addEventListener('click', async () => {
  sudoButton.disabled = true;
  try {
    const result = await api('system/sudo', { action: 'identity' });
    logService(result.message, !result.available);
  } catch (error) { logService(error.message, true); }
  finally { sudoButton.disabled = false; }
});

try {
  const bootstrap = await api('bootstrap');
  schema = bootstrap.schema;
  token = bootstrap.token;
  makeFields();
  setBusy(false);
  setStatus(bootstrap.warning || '');
  logService('PHP server ready. Python service and detector interface are not configured.');
} catch {
  setStatus('Cannot reach the PHP server. Start start.cmd and reload this page.', true);
}
