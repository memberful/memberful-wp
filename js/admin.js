/**
 * Helper for showing/hiding an element based on an input's value
 */
jQuery(document).ready(function($){
  var setupDependents = function(){
    // Prevent errors when initially hiding/showing elements on script run
    var setup      = false;
    var dependent  = $(this);
    var depends_on = dependent.attr('data-depends-on');
    var value_is   = dependent.attr('data-depends-value');

    var target     = $('input[name="'+depends_on+'"]');

    if ( ! target.length ) {
      target = $('#'+depends_on);
    }

    var callback = function() {
      var self = $(this);
      var checked = (self.prop('tagName') == 'SELECT') ? true : self.attr('checked');

      if (self.attr('value') == value_is && checked) {
        dependent.show();
        dependent.trigger('dependent:shown', self);
        setup = true;
      }
      else {
        dependent.hide();
        dependent.trigger('dependent:hidden', self);
      }
    }

    target.change(callback);

    // Initialise the section
    target.each(function(){
      if (setup)
        return

      $.proxy(callback, this)()
    });
  }

  $('[data-depends-on]').each(setupDependents);
  $(document).on('pjax:complete', function() {
    $('[data-depends-on]').each(setupDependents);
  });
})
