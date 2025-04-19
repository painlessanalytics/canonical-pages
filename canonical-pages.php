<?php
/**
 * Canonical Pages
 *
 * @package           Canonical Pages
 * @author            Painless Analytics
 * @copyright         2025 Painless Analytics
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Canonical Pages
 * Plugin URI:        https://www.painlessanalytics.com/canonical-pages-wordpress-plugin/
 * Description:       Quickly add the canonical meta tag and customize the url.
 * Version:           0.0.3
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * Author:            Painless Analytics
 * Author URI:        https://www.painlessanalytics.com
 * Text Domain:       canonical-pages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define plugin paths
define( 'CANONICAL_PAGES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CANONICAL_PAGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CANONICAL_PAGES_VERSION', '0.0.3');

if( !class_exists('canonicalPages') ) {
    require_once CANONICAL_PAGES_PLUGIN_PATH . 'canonical-pages.class.php';

    function canonical_pages_init() {
        canonicalPages::getInstance()->init();
    }
    add_action('init', 'canonical_pages_init');
}

if ( is_admin() && !class_exists('canonicalPagesAdmin') ) { // we are in admin mode
    require_once CANONICAL_PAGES_PLUGIN_PATH . 'admin/canonical-pages-admin.class.php'; // Admin page
}

// eof