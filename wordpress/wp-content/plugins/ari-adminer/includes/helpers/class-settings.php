<?php
namespace Ari_Adminer\Helpers;

define( 'ARIADMINER_SETTINGS_GROUP', 'ari_adminer' );
define( 'ARIADMINER_SETTINGS_NAME', 'ari_adminer_settings' );

define( 'ARIADMINER_SETTINGS_GENERAL_PAGE', 'ari-adminer-settings-general' );

define( 'ARIADMINER_SETTINGS_GENERAL_SECTION', 'ari_adminer_general_section' );

use Ari_Adminer\Helpers\Helper as Helper;
use Ari\Wordpress\Security as Security;

class Settings {
    static private $options = null;

    static private $default_settings = array(
        'theme' => ARIADMINER_THEME_DEFAULT,

        'mode' => 'adminer', // adminer, editor

        'roles' => array(),

        'stop_on_logout' => false,
    );

    public static function init() {
        register_setting(
            ARIADMINER_SETTINGS_GROUP,
            ARIADMINER_SETTINGS_NAME,
            array( __CLASS__, 'sanitize' )
        );

        add_settings_section(
            ARIADMINER_SETTINGS_GENERAL_SECTION,
            '', // Title
            array( __CLASS__, 'render_general_section_info' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE
        );

        add_settings_field(
            'theme',
            self::format_option_name(
                __( 'Adminer theme', 'ari-adminer' ),

                __( 'The selected theme will be used in Adminer application.', 'ari-adminer' )
            ),
            array( __CLASS__, 'render_general_theme' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE,
            ARIADMINER_SETTINGS_GENERAL_SECTION
        );

        add_settings_field(
            'mode',
            self::format_option_name(
                __( 'Mode', 'ari-adminer' ),

                __( 'If "Advanced" mode is selected, "Adminer" application will be used. It full-featured application for database management. When "Simple" option is chosen, "Adminer Editor" will be used. It has limited functionality.', 'ari-adminer' )
            ),
            array( __CLASS__, 'render_general_mode' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE,
            ARIADMINER_SETTINGS_GENERAL_SECTION
        );

        add_settings_field(
            'stop_on_logout',
            self::format_option_name(
                __( 'Stop "Adminer" on logout', 'ari-adminer' ),

                __( 'If the parameter is enabled, all "Adminer" sessions will be terminated when user is logged out from WordPress.', 'ari-adminer' )
            ),
            array( __CLASS__, 'render_general_stop_on_logout' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE,
            ARIADMINER_SETTINGS_GENERAL_SECTION
        );

        add_settings_field(
            'roles',
            self::format_option_name(
                __( 'Roles', 'ari-adminer' ),

                __( 'Only users with the selected user role will have access to the plugin.', 'ari-adminer' )
            ),
            array( __CLASS__, 'render_general_roles' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE,
            ARIADMINER_SETTINGS_GENERAL_SECTION
        );

        add_settings_field(
            'crypt_key',
            self::format_option_name(
                __( 'Crypt key', 'ari-adminer' ),

                __( 'Define an unique string which will be used to encrypt database passwords. Requires OpenSSL PHP extension.', 'ari-adminer' )
            ),
            array( __CLASS__, 'render_general_crypt_key' ),
            ARIADMINER_SETTINGS_GENERAL_PAGE,
            ARIADMINER_SETTINGS_GENERAL_SECTION
        );
    }

    public static function options() {
        if ( ! is_null( self::$options ) )
            return self::$options;

        self::$options = get_option( ARIADMINER_SETTINGS_NAME );

        return self::$options;
    }

    public static function get_option( $name, $default = null ) {
        $options = self::options();

        $val = $default;

        if ( isset( $options[$name] ) ) {
            $val = $options[$name];
        } else if ( is_null( $default) && isset( self::$default_settings[$name] ) ) {
            $val = self::$default_settings[$name];
        }

        return $val;
    }

    public static function format_option_name( $title, $tooltip = '' ) {
        $html = $title;

        if ( $tooltip ) {
            $html = sprintf(
                '<span class="tooltip" title="%2$s">%1$s</span>',
                $title,
                esc_attr( $tooltip )
            );
        }

        return $html;
    }

    public static function render_general_section_info() {
    }

    public static function render_general_theme() {
        $val = Helper::resolve_theme_name( self::get_option( 'theme' ) );
        $themes = Helper::get_themes();

        $html = sprintf(
            '<select id="ddlTheme" name="%1$s[theme]">',
            ARIADMINER_SETTINGS_NAME
        );

        foreach ( $themes as $theme ) {
            $html .= sprintf(
                '<option value="%1$s"%3$s>%2$s</option>',
                esc_attr($theme),
                $theme,
                $theme == $val ? ' selected="selected"' : ''
            );
        }

        $html .= '</select>';

        echo $html;
    }

    public static function render_general_mode() {
        $val = self::get_option( 'mode' );

        $html = sprintf(
            '<select id="ddlMode" name="%1$s[mode]">',
            ARIADMINER_SETTINGS_NAME
        );

        $options = array(
            'adminer' => __( 'Advanced', 'ari-adminer' ),

            'editor' => __( 'Simple', 'ari-adminer' ),
        );

        foreach ( $options as $key => $label ) {
            $html .= sprintf(
                '<option value="%1$s"%3$s>%2$s</option>',
                esc_attr( $key ),
                $label,
                $key == $val ? ' selected="selected"' : ''
            );
        }

        $html .= '</select>';

        echo $html;
    }

    public static function render_general_stop_on_logout() {
        $val = self::get_option( 'stop_on_logout' );

        $html = sprintf(
            '<p>
                <label>
                    <input type="checkbox" name="%1$s[stop_on_logout]" value="1" %2$s />
                    %3$s
                </label>
            </p>',
            ARIADMINER_SETTINGS_NAME,
            $val ? ' checked="checked"' : '',
            __( 'Stop on logout', 'ari-adminer' )
        );

        echo $html;
    }

    public static function render_general_roles() {
        $val = self::get_option( 'roles' );
        $html = '';
        $roles = Security::get_roles();
        $is_multi_site = is_multisite();

        foreach ( $roles as $role ) {
            $label = $role->name;

            $attrs = array(
                'autocomplete="off"'
            );
            if ( ! $is_multi_site && $role->has_cap( 'manage_options' ) ) {
                $attrs[] = 'checked';
                $attrs[] = 'disabled';
            } else {
                if ( in_array( $label, $val ) )
                    $attrs[] = 'checked';
            }

            $html .= sprintf(
                '<p>
                    <label>
                        <input type="checkbox" name="%1$s[roles][]" value="%2$s" %3$s />
                        %4$s
                    </label>
                </p>',
                ARIADMINER_SETTINGS_NAME,
                esc_attr( $label ),
                join( ' ', $attrs ),
                $label
            );
        }

        echo $html;
    }

    public static function render_general_crypt_key() {
        $val = Helper::get_crypt_key();

        $html = sprintf(
            '<p>
                <input type="text" name="%1$s[crypt_key]" id="tbxAdminerCryptKey" size="40" value="%2$s" />
            </p>',
            ARIADMINER_SETTINGS_NAME,
            esc_attr( $val )
        );

        echo $html;
    }

    public static function sanitize( $input ) {
        $new_input = array();

        foreach ( self::$default_settings as $key => $val ) {
            $type = gettype( $val );

            if ( 'boolean' == $type && ! isset( $input[$key] ) ) {
                $new_input[$key] = false;
            } else if ( 'array' == $type && ! isset( $input[$key] ) ) {
                $new_input[$key] = array();
            } else if ( isset( $input[$key] ) ) {
                $input_val = $input[$key];
                $filtered_val = null;
                switch ( $type ) {
                    case 'boolean':
                        $filtered_val = (bool) $input_val;
                        break;

                    case 'integer':
                        $filtered_val = intval( $input_val, 10 );
                        break;

                    case 'double':
                        $filtered_val = floatval( $input_val );
                        break;

                    case 'array':
                        $filtered_val = $input_val;
                        break;

                    case 'string':
                        $filtered_val = trim( $input_val );
                        break;
                }

                if ( ! is_null( $filtered_val) ) {
                    $new_input[$key] = $filtered_val;
                }
            }
        }

        $prev_roles = self::get_option( 'roles' );
        if ( ! is_multisite() || is_super_admin( get_current_user_id() ) ) {
            $roles = $new_input['roles'];

            $delete_roles = array_diff( $prev_roles, $roles );
            $add_roles = array_diff( $roles, $prev_roles );

            $wp_roles = Security::get_roles();

            foreach ( $wp_roles as $role ) {
                if ( $role->has_cap( 'manage_options' ) || in_array( $role->name, $add_roles ) ) {
                    if ( ! $role->has_cap( ARIADMINER_CAPABILITY_RUN ) ) {
                        $role->add_cap( ARIADMINER_CAPABILITY_RUN );
                    }
                } else if ( in_array( $role->name, $delete_roles ) ) {
                    if ( $role->has_cap( ARIADMINER_CAPABILITY_RUN ) ) {
                        $role->remove_cap( ARIADMINER_CAPABILITY_RUN );
                    }
                }
            }
        } else {
            $new_input['roles'] = $prev_roles;
        }

        $new_crypt_key = isset( $input['crypt_key'] ) ? trim( $input['crypt_key'] ) : '';
        $old_crypt_key = Helper::get_crypt_key();

        if ( empty( $new_crypt_key ) ) {
            if ( ! empty( $old_crypt_key ) ) {
                $new_crypt_key = $old_crypt_key;
            } else {
                $new_crypt_key = Helper::get_random_string();
            }
        }

        if ( ( $new_crypt_key != $old_crypt_key ) ) {
            if ( ! Helper::save_crypt_key( $new_crypt_key ) ) {
                add_settings_error(
                    'invalid-crypt-key',
                    '',
                    __( 'A crypt key could not be saved.', 'ari-adminer' ),
                    'error'
                );
            } else {
                if ( ! Helper::re_crypt_passwords( $new_crypt_key, $old_crypt_key ) ) {
                    add_settings_error(
                        'invalid-re-crypt-pass',
                        '',
                        __( 'Passwords could not be re-crypted with new crypt key.', 'ari-adminer' ),
                        'error'
                    );
                }
            }
        }

        return $new_input;
    }
}
