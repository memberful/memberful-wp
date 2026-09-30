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

const runRuntime = async ({ mode = 'free_meter', stored = null, countdownNode = null, countdownNodes = countdownNode ? [countdownNode] : [], limit = 3, bodies = [], template = null, prerendering = false }) => {
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
    getElementById: (id) => (id === 'memberful-metering-paywall' ? template : null),
    importNode: (node) => ({ clonedFrom: node }),
    querySelectorAll: (selector) => {
      if (selector === '[data-memberful-countdown]') {
        return countdownNodes;
      }
      if (selector === '.memberful-metering__start[data-memberful-metering="free"]') {
        return bodies.map((body) => body.start);
      }
      if (selector === '.memberful-metering__end[data-memberful-metering="free"]') {
        return bodies.map((body) => body.end);
      }
      return [];
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

// A minimal element tree: enough for the runtime to walk siblings, move the end marker and insert after it.
const makeNode = (name) => {
  const node = { name, styles: {}, parentElement: null, children: [] };
  node.style = { setProperty: (property, value, priority) => { node.styles[property] = `${value} ${priority}`; } };
  const siblings = () => (node.parentElement ? node.parentElement.children : []);
  Object.defineProperty(node, 'nextElementSibling', {
    get: () => siblings()[siblings().indexOf(node) + 1] || null,
  });
  node.append = (child) => {
    if (child.parentElement) {
      child.parentElement.children.splice(child.parentElement.children.indexOf(child), 1);
    }
    child.parentElement = node;
    node.children.push(child);
  };
  node.after = (child) => {
    if (child.parentElement) {
      child.parentElement.children.splice(child.parentElement.children.indexOf(child), 1);
    }
    const parent = node.parentElement;
    child.parentElement = parent;
    parent.children.splice(parent.children.indexOf(node) + 1, 0, child);
  };
  return node;
};

const makeBody = (home, blockCount) => {
  const start = makeNode('start');
  const end = makeNode('end');
  const blocks = Array.from({ length: blockCount }, (_, i) => makeNode(`block-${i}`));
  [start, ...blocks, end].forEach((node) => home.append(node));
  return { start, end, blocks };
};

const trippedViews = () => {
  const now = Math.floor(Date.now() / 1000);
  return { views: { 1: now, 2: now, 3: now } };
};

test('hides the body between every marker pair and puts the paywall after it when the meter trips', async () => {
  const home = makeNode('home');
  const title = makeNode('title');
  home.append(title);
  const first = makeBody(home, 2);
  const trailing = makeNode('share-buttons');
  home.append(trailing);
  const secondHome = makeNode('second-home');
  const second = makeBody(secondHome, 1);
  const template = { content: 'paywall' };

  await runRuntime({ stored: trippedViews(), bodies: [first, second], template });

  [...first.blocks, ...second.blocks].forEach((block) => assert.equal(block.styles.display, 'none important'));
  [title, trailing].forEach((node) => assert.equal(node.styles.display, undefined));
  assert.deepEqual(home.children.map((node) => node.name), ['title', 'start', 'block-0', 'block-1', 'end', undefined, 'share-buttons']);
  assert.equal(home.children[5].clonedFrom, 'paywall');
  assert.equal(secondHome.children[secondHome.children.length - 1].clonedFrom, 'paywall');
});

test('moves an end marker folded into the last block back beside the start', async () => {
  const home = makeNode('home');
  const body = makeBody(home, 2);
  body.blocks[1].append(body.end);

  await runRuntime({ stored: trippedViews(), bodies: [body], template: { content: 'paywall' } });

  assert.deepEqual(home.children.map((node) => node.name), ['start', 'block-0', 'block-1', 'end', undefined]);
  body.blocks.forEach((block) => assert.equal(block.styles.display, 'none important'));
});

test('puts an end marker pushed out of the body container at the end of that container', async () => {
  const outer = makeNode('outer');
  const home = makeNode('home');
  outer.append(home);
  const body = makeBody(home, 1);
  outer.append(body.end);

  await runRuntime({ stored: trippedViews(), bodies: [body], template: { content: 'paywall' } });

  assert.deepEqual(home.children.map((node) => node.name), ['start', 'block-0', 'end', undefined]);
  assert.equal(body.blocks[0].styles.display, 'none important');
});

test('leaves the body alone when the meter has not tripped', async () => {
  const home = makeNode('home');
  const body = makeBody(home, 2);

  await runRuntime({ bodies: [body], template: { content: 'paywall' } });

  body.blocks.forEach((block) => assert.equal(block.styles.display, undefined));
  assert.equal(home.children.length, 4);
});

test('does not count a prerendered page until it is shown', async () => {
  const result = await runRuntime({ prerendering: true });

  assert.deepEqual(result.readStored(), {});

  result.dispatch('prerenderingchange');

  assert.deepEqual(Object.keys(result.readStored().views), ['42']);
});
