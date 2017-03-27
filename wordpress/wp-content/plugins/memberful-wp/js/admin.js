/**
 * Helper for showing/hiding an element based on an input's value
 */
jQuery(document).ready(function($){

  var setupDependents = function(){
    var dependent    = $(this);
    var depends_on   = dependent.attr('data-depends-on');
    var value_is     = dependent.attr('data-depends-value');
    var value_is_not = dependent.attr('data-depends-value-not');

    var target     = $('input[name="'+depends_on+'"]');

	if ( ! target.length ) {
	  target = $('select[name="'+depends_on+'"]');
	}

    if ( ! target.length ) {
      target = $('#'+depends_on);
    }

    var callback = function() {
      var self = $(this);
      var field_value = self.attr('value');
      var dependent_can_be_shown = false;
      var fieldType = self.prop('tagName') == 'SELECT' ? 'select' : self.prop('type');
      var checked = self.is(':checked');

      if (fieldType != 'checkbox') {
        dependent_can_be_shown = (typeof value_is != "undefined" ? field_value == value_is : field_value != value_is_not);
      } else {
        dependent_can_be_shown = (typeof value_is != "undefined" ? checked : ! checked);
      }

      if (fieldType == 'radio') {
        dependent_can_be_shown = dependent_can_be_shown && checked;
      }

      if (dependent_can_be_shown) {
        dependent.show();
        dependent.trigger('dependent:shown', self);
      }
      else {
        dependent.hide();
        dependent.trigger('dependent:hidden', self);
      }
    }

    target.change(callback);

    // Initialise the section
    target.each(function(){
      $.proxy(callback, this)()
    });
  }

  $('[data-depends-on]').each(setupDependents);
  $(document).on('pjax:complete', function() {
    $('[data-depends-on]').each(setupDependents);
  });
})
