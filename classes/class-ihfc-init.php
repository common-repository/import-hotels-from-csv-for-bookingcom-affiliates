<?php

class IhfcInit {

    protected static $_instance = null;

    static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct() {
        add_action('init', array($this, 'ihfc_init_register_styles'));
        add_action('init', array($this, 'ihfc_init_register_script'));
        add_action('admin_print_styles-settings_page_' . IHFC_SETTINGS_OPTIONS_PAGE, array($this, 'ihfc_init_add_admin_css_js'));
        add_action('plugins_loaded', array($this, 'ihfc_init_plugins_loaded'));
        add_filter( 'plugin_action_links_' . IHFC_PLUGIN_FILE, array($this,'ihfc_init_plugin_settings_link'));
    }

    function ihfc_init_plugin_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page='.IHFC_SETTINGS_OPTIONS_PAGE.'">' . __( 'Settings', IHFC_TEXT_DOMAIN ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

    
    function ihfc_init_plugins_loaded() {
        load_plugin_textdomain(IHFC_TEXT_DOMAIN, false, dirname(IHFC_PLUGIN_FILE) . '/languages/');
    }

    function ihfc_init_register_styles() {
        wp_register_style('ihfc_admin_css', IHFC_PLUGIN_DIR_URL . 'css/ihfc_admin.css', '', IHFC_PLUGIN_SCRIPT_VERSION);
    }

    function ihfc_init_register_script() {
        wp_enqueue_script('jquery');
        wp_register_script('ihfc_admin_js', IHFC_PLUGIN_DIR_URL . 'js/ihfc_admin.js', array(
            'jquery'
                ), IHFC_PLUGIN_SCRIPT_VERSION, true);
    }

    function ihfc_init_add_admin_css_js() {
        wp_enqueue_style('ihfc_admin_css');
        wp_enqueue_script('ihfc_admin_js');
    }

    static function initAll() {
        IhfcInit::get_instance();
        IhfcAdminSettings::get_instance();
        $fr=IhfcFrontReplacemets::get_instance();
        $fr->init();
        
    }

}
