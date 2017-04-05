<?php
define( 'ARIADMINER_VERSION', '1.1.0' );
define( 'ARIADMINER_SLUG', 'ari-adminer' );
define( 'ARIADMINER_ASSETS_URL', ARIADMINER_URL . 'assets/' );
define( 'ARIADMINER_VERSION_OPTION', 'ari_adminer' );
define( 'ARIADMINER_INSTALL_PATH', ARIADMINER_PATH . 'install/' );
define( 'ARIADMINER_CAPABILITY_RUN', 'run_adminer' );

define( 'ARIADMINER_CONFIG_PATH', WP_CONTENT_DIR . '/ari-adminer-config.php' );
define( 'ARIADMINER_CONFIG_TMPL', "<?php\r\ndefined( 'ABSPATH' ) or die( 'Access forbidden!' );\r\ndefine( 'ARIADMINER_CRYPT_KEY', '%1\$s' );" );

define( 'ARIADMINER_THEMES_PATH', ARIADMINER_PATH . 'assets/themes/' );
define( 'ARIADMINER_THEMES_URL', ARIADMINER_URL . 'assets/themes/' );
define( 'ARIADMINER_THEME_DEFAULT', 'flat' );

define( 'ARIADMINER_MESSAGETYPE_SUCCESS', 'success' );
define( 'ARIADMINER_MESSAGETYPE_NOTICE', 'notice' );
define( 'ARIADMINER_MESSAGETYPE_ERROR', 'error' );
define( 'ARIADMINER_MESSAGETYPE_WARNING', 'warning' );
