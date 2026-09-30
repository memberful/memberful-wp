const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const runtime = fs.readFileSync(path.join(__dirname, '../../js/src/metering.js'), 'utf8');

const makeCountdownNode = (attributes) => ({
  getAttribute: (name) => (name in attributes ? attributes[name] : null),
  textContent: '',
  hidden: true,
});

const runRuntime = async ({ mode = 'free_meter', stored = null, countdownNode = null, countdownNodes = countdownNode ? [countdownNode] : [], limit = 3, freePaywalls = [], prerendering = false }) => {
  const values = new Map();
  const listeners = {};

  if (stored) {
    values.set('memberful_metering', JSON.stringify(stored));
  }

  const localStorage = {
    getItem: (key) => values.get(key) || null,
    setItem: (key, value) => values.set(key, value),
  };
  const document = {
    prerendering,
    addEventListener: (event, handler, options) => {
      (listeners[event] = listeners[event] || []).push({ handler, once: Boolean(options && options.once) });
    },
    documentElement: { classList: { add: () => {} } },
    querySelectorAll: (selector) => {
      if (selector === '[data-memberful-countdown]') {
        return countdownNodes;
      }
      return selector === '.memberful-metering__paywall[data-memberful-metering="free"]' ? freePaywalls : [];
    },
  };
  const window = {
    localStorage,
    memberfulMetering: {
      limit,
      mode,
      periodDays: 30,
      postId: 42,
      storageKey: 'memberful_metering',
    },
  };

  vm.runInNewContext(runtime, { document, window });

  return {
    readStored: () => JSON.parse(values.get('memberful_metering') || '{}'),
    dispatch: (event) => {
      // Mirror the browser: activation flips prerendering to false before the event fires, and once-listeners go.
      if (event === 'prerenderingchange') {
        document.prerendering = false;
      }
      const queued = listeners[event] || [];
      listeners[event] = queued.filter((entry) => !entry.once);
      queued.forEach((entry) => entry.handler());
    },
  };
};

const countdownTemplates = {
  'data-memberful-template': 'You have {count} free articles left.',
  'data-memberful-template-singular': 'You have {count} free article left.',
  'data-memberful-template-last': 'This is your last free article.',
};

test('records a newly allowed public view', async () => {
  const result = await runRuntime({});

  assert.deepEqual(Object.keys(result.readStored().views), ['42']);
});

test('keeps counting from an older stored history and drops its retired sync queue', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const result = await runRuntime({
    stored: { views: { 10: timestamp }, pending: { 10: timestamp } },
  });

  assert.deepEqual(result.readStored(), { views: { 10: timestamp, 42: result.readStored().views['42'] } });
});

test('does nothing outside the free meter mode', async () => {
  const result = await runRuntime({ mode: 'none' });

  assert.deepEqual(result.readStored(), {});
});

test('renders the plural countdown template while several free views remain', async () => {
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNode: node });

  assert.equal(node.textContent, 'You have 2 free articles left.');
  assert.equal(node.hidden, false);
});

test('hydrates every countdown on the page, not just the first', async () => {
  const templateNode = makeCountdownNode(countdownTemplates);
  const bodyNode = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNodes: [templateNode, bodyNode] });

  assert.equal(templateNode.textContent, 'You have 2 free articles left.');
  assert.equal(bodyNode.textContent, 'You have 2 free articles left.');
});

test('renders the singular countdown template when one free view remains', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNode: node, stored: { views: { 10: timestamp } } });

  assert.equal(node.textContent, 'You have 1 free article left.');
});

test('renders the last-article countdown template when no free views remain', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNode: node, stored: { views: { 10: timestamp, 11: timestamp } } });

  assert.equal(node.textContent, 'This is your last free article.');
});

test('leaves the countdown hidden when the selected template is empty', async () => {
  const node = makeCountdownNode({ 'data-memberful-template': 'You have {count} free articles left.' });
  const timestamp = Math.floor(Date.now() / 1000);
  await runRuntime({ countdownNode: node, stored: { views: { 10: timestamp } } });

  assert.equal(node.hidden, true);
});

test('leaves the countdown hidden when the meter blocks the view', async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNode: node, stored: { views: { 10: timestamp, 11: timestamp, 12: timestamp } } });

  assert.equal(node.hidden, true);
  assert.equal(node.textContent, '');
});

test('leaves the countdown hidden when the free limit is zero', async () => {
  const node = makeCountdownNode(countdownTemplates);
  await runRuntime({ countdownNode: node, limit: 0 });

  assert.equal(node.hidden, true);
});

test('hides the body blocks before every free paywall and shows the paywall when the meter trips', async () => {
  const makeBlock = () => {
    const block = { styles: {} };
    block.style = { setProperty: (name, value, priority) => { block.styles[name] = `${value} ${priority}`; } };
    return block;
  };
  const home = {};
  const makePaywall = (blocks) => {
    const paywall = { hidden: true, parentElement: home, closest: () => home };
    paywall.previousElementSibling = blocks[blocks.length - 1];
    blocks.forEach((block, i) => { block.previousElementSibling = blocks[i - 1] || null; });
    return paywall;
  };
  const firstBody = [makeBlock(), makeBlock()];
  const secondBody = [makeBlock()];
  const paywalls = [makePaywall(firstBody), makePaywall(secondBody)];

  // An unclosed tag in the body folded this paywall into its last block; the runtime moves it back beside the body.
  const folded = makeBlock();
  folded.parentElement = home;
  folded.previousElementSibling = null;
  const foldedPaywall = { hidden: true, closest: () => home, parentElement: folded, previousElementSibling: null };
  folded.after = (node) => {
    node.parentElement = home;
    node.previousElementSibling = folded;
  };
  paywalls.push(foldedPaywall);

  const now = Math.floor(Date.now() / 1000);

  await runRuntime({ stored: { views: { 1: now, 2: now, 3: now } }, freePaywalls: paywalls });

  paywalls.forEach((paywall) => assert.equal(paywall.hidden, false));
  [...firstBody, ...secondBody, folded].forEach((block) => assert.equal(block.styles.display, 'none important'));
  assert.equal(foldedPaywall.parentElement, home);
});

test('does not count a prerendered page until it is shown', async () => {
  const result = await runRuntime({ prerendering: true });

  assert.deepEqual(result.readStored(), {});

  result.dispatch('prerenderingchange');

  assert.deepEqual(Object.keys(result.readStored().views), ['42']);
});
