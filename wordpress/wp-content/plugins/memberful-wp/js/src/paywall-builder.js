/**
 * Paywall builder admin script.
 */

jQuery(function ($) {
  $('.memberful-paywall-builder__color').wpColorPicker();

  const $modeInputs = $('input[name="memberful_paywall[mode]"]');
	const $panels     = $('.memberful-paywall-builder__panel');

  function applyMode(mode) {
    $panels.each(function () {
      this.style.display = this.dataset.panel === mode ? '' : 'none';
    });
  }

  $modeInputs.on('change', function () {
    if (this.checked) {
      applyMode(this.value);
    }
  });

  applyMode($modeInputs.filter(':checked').val() || 'builder');
});