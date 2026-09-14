jQuery(document).ready(function($) {
  var selector           = "[data-behaviour=memberful_nav_menu_links]"
  var buttonSelector     = selector + " [data-behaviour=add_link]";
  var checkboxesSelector = selector + " input[type=checkbox]";
  var spinnerSelector    = selector + " .spinner";

  $(buttonSelector).on("click", function() {
    if($(checkboxesSelector + ":checked").length > 0) {
      showSpinner();
      addLinksToMenu();
    }
  });

  // private

  function addLinksToMenu() {
    $(checkboxesSelector + ":checked").each(function() {
      var url   = $(this).data("url");
      var label = $(this).data("label");

      addLinkToMenu(url, label);
    });
  }

  function addLinkToMenu(url, label) {
    wpNavMenu.addLinkToMenu(url, label, null, function() {
      uncheckCheckboxes();
      hideSpinner();
    });
  }

  function uncheckCheckboxes() {
    $(checkboxesSelector).prop("checked", false);
  }

  function showSpinner() {
    $(spinnerSelector).addClass("is-active");
  }

  function hideSpinner() {
    $(spinnerSelector).removeClass("is-active");
  }
});
