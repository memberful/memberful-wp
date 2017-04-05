jQuery(document).on('app_ready', function(e, app) {
    var $ = jQuery;

    $('.tooltip', '#ari_adminer_plugin').tooltip({
        position: {
            within: '#ari_adminer_plugin'
        }
    });
});