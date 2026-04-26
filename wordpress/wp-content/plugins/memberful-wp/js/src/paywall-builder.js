jQuery(function ($) {
  const $form       = $('.memberful-paywall-builder__panel[data-panel="builder"]');
  const $modeInputs = $('input[name="memberful_paywall[mode]"]');
  const $panels     = $('.memberful-paywall-builder__panel');
  const $preview    = $('#mf-paywall-preview');
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
      heading_tag:    $('#memberful-paywall-heading-tag').val() || 'h2',
      subheading:     $('#memberful-paywall-subheading').val() || '',
      features:       $('#memberful-paywall-features').val() || '',
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
      body.append('config[' + key + ']', config[key]);
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

  if (($modeInputs.filter(':checked').val() || 'builder') === 'builder') {
    refreshPreview();
  }
});
