<?php

/**
 * Plugin Name: Cocote Feed
 * Plugin URI: https://github.com/Cocote-Team/WooCommercePlugin
 * Description: Exporter le catalogue Woocommerce dans un flux XML pour Cocote
 * Version: 1.1.3
 * Author: Cocote Team
 * Author URI: https://fr.cocote.com
 * Text Domain: woo-cocotefeed
 * Domain Path: /languages
 *
 * Woo:
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.3
 *
 * Copyright: © 2018 Publish-it.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Prevent Data Leaks
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WooCocoteFeed
{
    private static $instance = false;
    public $submenu;

    /**
     * WooCocoteFeed constructor.
     */
    public function __construct()
    {
        $this->submenu = 'cocote-submenu-page';

        include_once plugin_dir_path(__FILE__) . 'classes/install-database.php';
        new install_database();
        include_once plugin_dir_path(__FILE__) . 'classes/cocotefeed.php';
        new Cocotefeed();

        register_activation_hook(__FILE__, array('install_database', 'install'));
        register_uninstall_hook(__FILE__, array('install_database', 'uninstall'));
        register_activation_hook(__FILE__, array('Cocotefeed','cron_cocote_generate_xml'));

        add_action('admin_menu', array($this, 'wpshout_enqueue_styles'));
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'baw_settings_action_links'), 10, 2);
        add_action('wp_head', array($this, 'tracking_script_js'));
    }

    /**
     * Tracking Script JS function
     */
    public function tracking_script_js()
    {
        $resultat = check_cocote_export();
        if (isset($resultat->shop_id)) {
            $mSiteId = $resultat->shop_id;
            // insert tracking script js
            echo '<script src="https://js.cocote.com/script-fr.min.js"></script>';
            echo '<script type="text/javascript">';
            echo 'new CocoteTSA({
                        lang: "fr",
                        mSiteId: ' . $mSiteId . '
                    });
                </script>'
            ;
        }
    }

    /**
     * WPshout Enqueue Styles function
     */
    public function wpshout_enqueue_styles()
    {
        $file_url = plugins_url('assets/css/cocotefeed.css', __FILE__);

        // Actually load up the stylesheet
        wp_enqueue_style('sp_sytlesheet', $file_url);
    }

    /**
     * WooCommerce Analytics is only available
     *
     * @return bool
     */
    public static function shouldTrackStore()
    {
        /**
         * Check if WooCommerce is active
         **/
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return false;
        }

        return true;
    }

    public function baw_settings_action_links($links, $file)
    {
        // Plugin Settings
        array_unshift($links, '<a href="' . admin_url('admin.php?page=' . $this->submenu) . '">' . ('Settings') . '</a>');

        return $links;
    }

    /**
     * Function to instantiate our class and make it a singleton
     */
    public static function get_instance()
    {
        if (!self::shouldTrackStore()) {
            return;
        }

        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

include plugin_dir_path(__FILE__) . 'includes/functions.php';

$woo_cocote_feed = WooCocoteFeed::get_instance();
