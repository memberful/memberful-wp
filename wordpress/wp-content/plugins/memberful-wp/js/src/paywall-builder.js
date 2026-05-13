jQuery(function ($) {
  const $form       = $('.memberful-paywall-builder__panel[data-panel="builder"]');
  const $modeInputs = $('input[name="memberful_paywall[mode]"]');
  const $panels     = $('.memberful-paywall-builder__panel');
  const $preview    = $('#memberful-paywall-preview');
  const $colorInput = $('.memberful-paywall-builder__color');

  const preview = window.memberfulPaywallPreview || {};
  const DEBOUNCE_MS = 250;

  let debounceTimer = null;
  let requestSeq = 0;

  $colorInput.wpColorPicker({
    change: function () {
      setTimeout(scheduleRefresh, 0);
    },
    clear: function () {
      setTimeout(scheduleRefresh, 0);
    },
  });

  function applyMode(mode) {
    $panels.each(function () {
      this.style.display = this.dataset.panel === mode ? '' : 'none';
    });
  }

  $modeInputs.on('change', function () {
    if (!this.checked) {
      return;
    }

    applyMode(this.value);

    if (this.value === 'builder') {
      refreshPreview();
    }
  });

  applyMode($modeInputs.filter(':checked').val() || 'builder');

  function collectConfig() {
    return {
      mode:           $('input[name="memberful_paywall[mode]"]:checked').val() || 'builder',
      layout:         $('input[name="memberful_paywall[layout]"]:checked').val() || 'card',
      heading:        $('#memberful-paywall-heading').val() || '',
      subheading:     $('#memberful-paywall-subheading').val() || '',
      features:       $('#memberful-paywall-benefits .memberful-paywall-builder__benefit-input').map(function () {
        return $(this).val();
      }).get(),
      button_label:   $('#memberful-paywall-button-label').val() || '',
      subscribe_url:  $('#memberful-paywall-subscribe-url').val() || '',
      sign_in_url:    $('#memberful-paywall-signin-url').val() || '',
      brand_color:    $colorInput.val() || '',
      button_shape:   $('#memberful-paywall-button-shape').val() || 'rounded',
    };
  }

  function refreshPreview() {
    if (!preview.ajaxUrl || !preview.action || !preview.nonce || !$preview.length) {
      return;
    }

    clearTimeout(debounceTimer);

    const seq = ++requestSeq;
    const body = new URLSearchParams();
    body.set('action', preview.action);
    body.set('nonce', preview.nonce);

    const config = collectConfig();
    Object.keys(config).forEach(function (key) {
      const value = config[key];
      if (Array.isArray(value)) {
        value.forEach(function (item) {
          body.append('config[' + key + '][]', item);
        });
      } else {
        body.append('config[' + key + ']', value);
      }
    });

    fetch(preview.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    })
    .then(function (res) {
      if (!res.ok) {
        throw new Error('Request failed: ' + res.status);
      }

      return res.json();
    })
    .then(function (json) {
      if (seq !== requestSeq) {
        return;
      }

      if (json && json.success && json.data && json.data.html) {
        $preview.attr('srcdoc', json.data.html);
      }
    })
    .catch(function (err) {
      console.error('Paywall preview failed', err);
    });
  }

  function scheduleRefresh() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(refreshPreview, DEBOUNCE_MS);
  }

  $form.on('input', 'input[type="text"], input[type="url"], textarea', scheduleRefresh);
  $form.on('change', 'input[type="radio"], select', refreshPreview);

  $form.on('click', '.memberful-paywall-builder__benefit-add', function (e) {
    e.preventDefault();

    const template = document.getElementById('memberful-paywall-benefit-template');
    const list     = document.getElementById('memberful-paywall-benefits');
    if (!template || !list) {
      return;
    }

    list.appendChild(template.content.cloneNode(true));
    $(list).find('.memberful-paywall-builder__benefit-input').last().trigger('focus');
    refreshPreview();
  });

  $form.on('click', '.memberful-paywall-builder__benefit-remove', function (e) {
    e.preventDefault();
    $(this).closest('.memberful-paywall-builder__benefit').remove();
    refreshPreview();
  });

  if (($modeInputs.filter(':checked').val() || 'builder') === 'builder') {
    refreshPreview();
  }
});
