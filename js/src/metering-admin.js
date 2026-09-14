/**
 * Metering rule builder.
 */
(() => {
  const ns = window.memberfulMeteringAdmin;
  const domReady = window.wp && window.wp.domReady;

  if (!ns || !domReady) {
    return;
  }

  const apiFetch = window.wp && window.wp.apiFetch;
  const operators = ns.operators || {};
  const postTypes = ns.postTypes || {};
  const labels = ns.labels || {};

  const DEBOUNCE_MS = 200;
  const REST_PER_PAGE = 20;

  const SCOPES = [
    { scope: 'rules', containerId: 'memberful-metering-include-groups', addId: 'memberful-metering-add-group' },
    { scope: 'exclude_rules', containerId: 'memberful-metering-exclude-groups', addId: 'memberful-metering-add-exclude-group' },
  ];

  // Per search-input debounce timer + request sequence.
  const searchState = new WeakMap();

  const initScope = ({ scope, containerId, addId }) => {
    const container = document.getElementById(containerId);
    const addButton = document.getElementById(addId);

    if (!container) {
      return;
    }

    container.addEventListener('click', onContainerClick);
    container.addEventListener('change', onContainerChange);
    container.addEventListener('input', onContainerInput);
    container.addEventListener('keydown', onContainerKeydown);

    if (addButton) {
      addButton.addEventListener('click', () => addGroup(container, scope));
    }

    container.querySelectorAll('.memberful-metering-rule-group').forEach(updateConditionRemovers);
    updateEmptyState(container);
  };

  const addGroup = (container, scope) => {
    const template = document.getElementById(`memberful-metering-${scope}-group-template`);

    if (!template) {
      return;
    }

    const groupIndex = parseInt(container.dataset.nextGroupIndex || '0', 10);
    const group = template.content.querySelector('.memberful-metering-rule-group').cloneNode(true);

    applyTokens(group, { __GROUP__: groupIndex });
    group.dataset.groupIndex = String(groupIndex);

    container.appendChild(group);
    container.dataset.nextGroupIndex = String(groupIndex + 1);

    addCondition(group);
    updateEmptyState(container);

    const field = group.querySelector('.memberful-metering-condition-field');
    if (field) {
      field.focus();
    }
  };

  const addCondition = (group) => {
    const template = document.getElementById('memberful-metering-condition-template');

    if (!template) {
      return;
    }

    const conditionIndex = parseInt(group.dataset.nextConditionIndex || '0', 10);
    const condition = template.content.querySelector('.memberful-metering-condition').cloneNode(true);

    applyTokens(condition, {
      __SCOPE__: group.dataset.scope,
      __GROUP__: group.dataset.groupIndex,
      __COND__: conditionIndex,
    });

    group.querySelector('.memberful-metering-conditions').appendChild(condition);
    group.dataset.nextConditionIndex = String(conditionIndex + 1);
    updateConditionRemovers(group);
  };

  const onContainerClick = (event) => {
    const addConditionButton = event.target.closest('.memberful-metering-add-condition');
    if (addConditionButton) {
      addCondition(addConditionButton.closest('.memberful-metering-rule-group'));
      return;
    }

    const removeConditionButton = event.target.closest('.memberful-metering-remove-condition');
    if (removeConditionButton) {
      const group = removeConditionButton.closest('.memberful-metering-rule-group');
      removeConditionButton.closest('.memberful-metering-condition').remove();
      updateConditionRemovers(group);
      return;
    }

    const removeGroupButton = event.target.closest('.memberful-metering-remove-group');
    if (removeGroupButton) {
      const container = removeGroupButton.closest('.memberful-metering-groups');
      removeGroupButton.closest('.memberful-metering-rule-group').remove();
      updateEmptyState(container);
      return;
    }

    const chipRemove = event.target.closest('.memberful-metering-chip__remove');
    if (chipRemove) {
      chipRemove.closest('.memberful-metering-chip').remove();
      return;
    }

    const option = event.target.closest('.memberful-metering-tokenfield__option');
    if (option) {
      selectOption(option);
    }
  };

  const onContainerChange = (event) => {
    const fieldSelect = event.target.closest('.memberful-metering-condition-field');
    if (fieldSelect) {
      rebuildCondition(fieldSelect.closest('.memberful-metering-condition'), fieldSelect.value);
    }
  };

  const onContainerInput = (event) => {
    const input = event.target.closest('.memberful-metering-tokenfield__input');
    if (input) {
      scheduleSearch(input.closest('.memberful-metering-tokenfield'));
    }
  };

  const onContainerKeydown = (event) => {
    const input = event.target.closest('.memberful-metering-tokenfield__input');
    if (input) {
      handleTokenfieldKeydown(event, input.closest('.memberful-metering-tokenfield'), input);
    }
  };

  const rebuildCondition = (condition, field) => {
    condition.dataset.field = field;

    const prefix = conditionPrefix(condition);
    const operatorSelect = condition.querySelector('.memberful-metering-condition-operator');

    if (operatorSelect) {
      operatorSelect.innerHTML = '';
      Object.entries(operators[field] || {}).forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        operatorSelect.appendChild(option);
      });
    }

    const valuesCell = condition.querySelector('.memberful-metering-condition-values');
    valuesCell.innerHTML = '';
    valuesCell.appendChild(buildValuesControl(field, `${prefix}[values]`));
  };

  const conditionPrefix = (condition) => {
    const fieldSelect = condition.querySelector('.memberful-metering-condition-field');
    return fieldSelect ? fieldSelect.getAttribute('name').replace(/\[field\]$/, '') : '';
  };

  const buildValuesControl = (field, name) => {
    if (field === 'url') {
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'regular-text memberful-metering-condition-text';
      input.name = name;
      return input;
    }

    // Clone the empty token field from the condition template so its markup
    // (placeholder, results list) stays defined only in PHP.
    const template = document.getElementById('memberful-metering-condition-template');
    const tokenfield = template.content.querySelector('.memberful-metering-tokenfield').cloneNode(true);

    tokenfield.dataset.field = field;
    tokenfield.dataset.name = name;
    tokenfield.querySelectorAll('.memberful-metering-chip').forEach((chip) => chip.remove());
    closeList(tokenfield);

    return tokenfield;
  };

  const selectOption = (option) => {
    const tokenfield = option.closest('.memberful-metering-tokenfield');
    const input = tokenfield.querySelector('.memberful-metering-tokenfield__input');

    addChip(tokenfield, option.dataset.value, option.dataset.label);
    input.value = '';
    closeList(tokenfield);
    input.focus();
  };

  const addChip = (tokenfield, value, label) => {
    if (!value) {
      return;
    }

    const alreadySelected = Array.from(
      tokenfield.querySelectorAll('.memberful-metering-chip input[type="hidden"]')
    ).some((hidden) => hidden.value === value);

    if (alreadySelected) {
      return;
    }

    const template = document.getElementById('memberful-metering-chip-template');
    const chip = template.content.firstElementChild.cloneNode(true);

    chip.querySelector('.memberful-metering-chip__label').textContent = label || value;

    const hidden = chip.querySelector('input[type="hidden"]');
    hidden.value = value;
    hidden.name = `${tokenfield.dataset.name}[]`;

    tokenfield.insertBefore(chip, tokenfield.querySelector('.memberful-metering-tokenfield__input'));
  };

  const scheduleSearch = (tokenfield) => {
    const input = tokenfield.querySelector('.memberful-metering-tokenfield__input');
    const state = searchState.get(input) || {};

    clearTimeout(state.timer);
    state.timer = setTimeout(() => runSearch(tokenfield), DEBOUNCE_MS);
    searchState.set(input, state);
  };

  const runSearch = (tokenfield) => {
    const input = tokenfield.querySelector('.memberful-metering-tokenfield__input');
    const query = input.value.trim();

    if (!query) {
      closeList(tokenfield);
      return;
    }

    const state = searchState.get(input) || {};
    const seq = (state.seq || 0) + 1;
    state.seq = seq;
    searchState.set(input, state);

    const isCurrent = () => (searchState.get(input) || {}).seq === seq;

    searchOptions(tokenfield.dataset.field, query)
      .then((results) => {
        if (isCurrent()) {
          renderList(tokenfield, withoutSelected(tokenfield, results));
        }
      })
      .catch((error) => {
        console.error('Memberful metering search failed', error);
        if (isCurrent()) {
          renderList(tokenfield, []);
        }
      });
  };

  const searchOptions = (field, query) => {
    if (field === 'post_type') {
      const needle = query.toLowerCase();
      const results = Object.keys(postTypes)
        .filter((slug) => slug.toLowerCase().includes(needle) || String(postTypes[slug]).toLowerCase().includes(needle))
        .map((slug) => ({ value: slug, label: postTypes[slug] }));

      return Promise.resolve(results);
    }

    if (!apiFetch) {
      return Promise.resolve([]);
    }

    const taxonomy = field === 'tag' ? 'tags' : 'categories';
    const path = `/wp/v2/${taxonomy}?search=${encodeURIComponent(query)}&per_page=${REST_PER_PAGE}&_fields=id,name,slug`;

    return apiFetch({ path }).then((terms) =>
      (Array.isArray(terms) ? terms : []).map((term) => ({
        value: String(term.slug || '').toLowerCase(),
        label: term.name || '',
      }))
    );
  };

  const withoutSelected = (tokenfield, results) => {
    const selected = new Set(
      Array.from(tokenfield.querySelectorAll('.memberful-metering-chip input[type="hidden"]')).map((hidden) => hidden.value)
    );

    return results.filter((result) => !selected.has(result.value));
  };

  const renderList = (tokenfield, results) => {
    const list = tokenfield.querySelector('.memberful-metering-tokenfield__list');

    if (!list) {
      return;
    }

    list.innerHTML = '';

    if (!results.length) {
      const empty = document.createElement('li');
      empty.className = 'memberful-metering-tokenfield__empty';
      empty.textContent = labels.noResults || '';
      list.appendChild(empty);
      list.hidden = false;
      return;
    }

    results.forEach((result, index) => {
      const option = document.createElement('li');
      option.className = 'memberful-metering-tokenfield__option';
      option.setAttribute('role', 'option');
      option.dataset.value = result.value;
      option.dataset.label = result.label;
      option.textContent = result.label;

      if (index === 0) {
        option.classList.add('is-active');
      }

      list.appendChild(option);
    });

    list.hidden = false;
  };

  const closeList = (tokenfield) => closeListElement(tokenfield.querySelector('.memberful-metering-tokenfield__list'));

  const closeListElement = (list) => {
    if (list) {
      list.innerHTML = '';
      list.hidden = true;
    }
  };

  const handleTokenfieldKeydown = (event, tokenfield, input) => {
    const list = tokenfield.querySelector('.memberful-metering-tokenfield__list');
    const options = list && !list.hidden ? Array.from(list.querySelectorAll('.memberful-metering-tokenfield__option')) : [];

    switch (event.key) {
      case 'ArrowDown':
        if (options.length) {
          event.preventDefault();
          moveActiveOption(options, 1);
        }
        break;
      case 'ArrowUp':
        if (options.length) {
          event.preventDefault();
          moveActiveOption(options, -1);
        }
        break;
      case 'Enter': {
        // Never submit the form from the search box.
        event.preventDefault();
        const active = options.find((option) => option.classList.contains('is-active')) || options[0];
        if (active) {
          selectOption(active);
        }
        break;
      }
      case 'Escape':
        closeList(tokenfield);
        break;
      case 'Backspace':
        if (input.value === '') {
          const chips = tokenfield.querySelectorAll('.memberful-metering-chip');
          if (chips.length) {
            chips[chips.length - 1].remove();
          }
        }
        break;
      default:
        break;
    }
  };

  const moveActiveOption = (options, delta) => {
    const currentIndex = options.findIndex((option) => option.classList.contains('is-active'));
    let nextIndex = currentIndex + delta;

    if (nextIndex < 0) {
      nextIndex = options.length - 1;
    } else if (nextIndex >= options.length) {
      nextIndex = 0;
    }

    options.forEach((option) => option.classList.remove('is-active'));
    options[nextIndex].classList.add('is-active');
  };

  const applyTokens = (root, tokens) => {
    ['name', 'data-name'].forEach((attribute) => {
      root.querySelectorAll(`[${attribute}]`).forEach((element) => {
        let value = element.getAttribute(attribute);

        Object.keys(tokens).forEach((token) => {
          value = value.split(token).join(tokens[token]);
        });

        element.setAttribute(attribute, value);
      });
    });
  };

  // A group must keep at least one condition, so disable its remove buttons when only one remains.
  const updateConditionRemovers = (group) => {
    const removers = group.querySelectorAll('.memberful-metering-remove-condition');
    const disable = removers.length <= 1;
    removers.forEach((remover) => {
      remover.disabled = disable;
    });
  };

  const updateEmptyState = (container) => {
    const empty = container.querySelector('.memberful-metering-empty');
    if (empty) {
      empty.hidden = container.querySelector('.memberful-metering-rule-group') !== null;
    }
  };

  domReady(() => {
    SCOPES.forEach(initScope);

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.memberful-metering-tokenfield')) {
        document.querySelectorAll('.memberful-metering-tokenfield__list').forEach(closeListElement);
      }
    });
  });
})();
