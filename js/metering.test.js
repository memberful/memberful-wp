const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const runtime = fs.readFileSync(path.join(__dirname, 'src/metering.js'), 'utf8');

const makeCountdownNode = (attributes) => ({
  getAttribute: (name) => (name in attributes ? attributes[name] : null),
  textContent: '',
  hidden: true,
});

const runRuntime = async ({ mode, stored = null, response = { success: true, data: {} }, countdownNode = null, countdownNodes = countdownNode ? [countdownNode] : [], limit = 3, container = null }) => {
  const requests = [];
  const values = new Map();

  if (stored) {
    values.set('memberful_metering', JSON.stringify(stored));
  }

  const localStorage = {
    getItem: (key) => values.get(key) || null,
    setItem: (key, value) => values.set(key, value),
  };
  const document = {
    documentElement: { classList: { add: () => {} } },
    querySelector: (selector) => {
      if (selector === '[data-memberful-countdown]') {
        return countdownNodes[0] || null;
      }
      if (selector === '.memberful-metering') {
        return container;
      }
      return null;
    },
    querySelectorAll: (selector) => (selector === '[data-memberful-countdown]' ? countdownNodes : []),
  };
  const window = {
    URLSearchParams,
    localStorage,
    memberfulMetering: {
      action: 'memberful_metering_sample',
      ajaxUrl: '/wp-admin/admin-ajax.php',
      limit,
      mode,
      periodDays: 30,
      postId: 42,
      storageKey: 'memberful_metering',
    },
    fetch: async (url, options) => {
      requests.push({ url, options });
      return { json: async () => response };
    },
  };

  vm.runInNewContext(runtime, { document, window });
  await new Promise((resolve) => setImmediate(resolve));

  return {
    requests,
    stored: JSON.parse(values.get('memberful_metering') || '{}'),
  };
};

test('queues and synchronizes a newly allowed public view', async () => {
  const result = await runRuntime({ mode: 'free_meter' });

  assert.equal(result.requests.length, 1);
  const body = new URLSearchParams(result.requests[0].options.body);
  assert.equal(body.get('op'), 'record_public');
  assert.deepEqual(body.getAll('post_ids[]'), ['42']);
});

test('removes server-acknowledged public views from the outbox', async () => {
  const result = await runRuntime({
    mode: 'free_meter',
    response: { success: true, data: { synced: [42] } },
  });

  assert.deepEqual(Object.keys(result.stored.views), ['42']);
  assert.deepEqual(result.stored.pending, {});
});

test('retains public views for retry when synchronization fails', async () => {
  const result = await runRuntime({
    mode: 'free_meter',
    response: { success: false, data: { code: 'rate_limited' } },
  });

  assert.equal(typeof result.stored.pending['42'], 'number');
});

test('retains public views omitted from a partial acknowledgement', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const result = await runRuntime({
    mode: 'free_meter',
    response: { success: true, data: { synced: [10] } },
    stored: {
      v: 2,
      pending: { 10: timestamp - 1, 42: timestamp },
      views: { 10: timestamp - 1, 42: timestamp },
    },
  });

  assert.deepEqual(Object.keys(result.stored.pending), ['42']);
});

test('sends all local views with a protected sample request', async () => {
  const result = await runRuntime({
    mode: 'protected_sample',
    stored: {
      pending: {},
      views: { 10: Math.floor(Date.now() / 1000) },
    },
  });

  assert.equal(result.requests.length, 1);
  const body = new URLSearchParams(result.requests[0].options.body);
  assert.equal(body.get('op'), 'sample');
  assert.deepEqual(body.getAll('public_post_ids[]'), ['10']);
});

const countdownTemplates = {
  'data-memberful-template': 'You have {count} free articles left.',
  'data-memberful-template-singular': 'You have {count} free article left.',
  'data-memberful-template-last': 'This is your last free article.',
};

test('renders the plural countdown template while several free views remain', async () => {
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ mode: 'free_meter', countdownNode: node });

  assert.equal(node.textContent, 'You have 2 free articles left.');
  assert.equal(node.hidden, false);
});

test('hydrates every countdown on the page, not just the first', async () => {
  const templateNode = makeCountdownNode(countdownTemplates);
  const bodyNode = makeCountdownNode(countdownTemplates);
  await runRuntime({ mode: 'free_meter', countdownNodes: [templateNode, bodyNode] });

  assert.equal(templateNode.textContent, 'You have 2 free articles left.');
  assert.equal(templateNode.hidden, false);
  assert.equal(bodyNode.textContent, 'You have 2 free articles left.');
  assert.equal(bodyNode.hidden, false);
});

test('renders the singular countdown template when one free view remains', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({
    mode: 'free_meter',
    countdownNode: node,
    stored: { v: 2, pending: {}, views: { 10: timestamp } },
  });

  assert.equal(node.textContent, 'You have 1 free article left.');
  assert.equal(node.hidden, false);
});

test('renders the last-article countdown template when no free views remain', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({
    mode: 'free_meter',
    countdownNode: node,
    stored: { v: 2, pending: {}, views: { 10: timestamp, 11: timestamp } },
  });

  assert.equal(node.textContent, 'This is your last free article.');
  assert.equal(node.hidden, false);
});

test('leaves the countdown hidden when the selected template is empty', async () => {
  const node = makeCountdownNode({ 'data-memberful-template': 'You have {count} free articles left.' });
  const timestamp = Math.floor(Date.now() / 1000);
  await runRuntime({
    mode: 'free_meter',
    countdownNode: node,
    stored: { v: 2, pending: {}, views: { 10: timestamp } },
  });

  assert.equal(node.hidden, true);
});

test('leaves the countdown hidden when the meter blocks the view', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({
    mode: 'free_meter',
    countdownNode: node,
    stored: { v: 2, pending: {}, views: { 10: timestamp, 11: timestamp, 12: timestamp } },
  });

  assert.equal(node.hidden, true);
  assert.equal(node.textContent, '');
});

test('leaves the countdown hidden when the free limit is zero', async () => {
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ mode: 'free_meter', countdownNode: node, limit: 0 });

  assert.equal(node.hidden, true);
  assert.equal(node.textContent, '');
});

test('hydrates template and released body countdowns after a protected sample release', async () => {
  const templateNode = makeCountdownNode(countdownTemplates);
  const bodyNode = makeCountdownNode(countdownTemplates);
  const countdownNodes = [templateNode];
  const content = {
    hidden: true,
    set innerHTML(html) {
      countdownNodes.push(bodyNode);
    },
  };
  const paywall = { hidden: false };
  const container = {
    querySelector: (selector) => {
      if (selector === '.memberful-metering__content') {
        return content;
      }
      if (selector === '.memberful-metering__paywall') {
        return paywall;
      }
      return null;
    },
  };

  await runRuntime({
    mode: 'protected_sample',
    container,
    countdownNodes,
    response: { success: true, data: { released: true, html: '<p>Full body</p>', remaining: 1, synced: [] } },
  });

  assert.equal(content.hidden, false);
  assert.equal(paywall.hidden, true);
  assert.equal(templateNode.textContent, 'You have 1 free article left.');
  assert.equal(templateNode.hidden, false);
  assert.equal(bodyNode.textContent, 'You have 1 free article left.');
  assert.equal(bodyNode.hidden, false);
});

test('acknowledges pending public views even when a protected sample is denied', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const result = await runRuntime({
    mode: 'protected_sample',
    response: {
      success: true,
      data: { released: false, remaining: 0, synced: [10] },
    },
    stored: { pending: { 10: timestamp }, views: { 10: timestamp } },
  });

  assert.deepEqual(result.stored.pending, {});
});
