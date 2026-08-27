<?php
/**
 * Canonical Pages
 *
 * @package           Canonical Pages
 * @author            Painless Analytics
 * @copyright         2026 Painless Analytics
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Canonical Pages
 * Plugin URI:        https://www.painlessanalytics.com/canonical-pages-wordpress-plugin/
 * Description:       Quickly add the canonical meta tag and customize the url.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Tested up to:      7.1
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
define( 'CANONICAL_PAGES_VERSION', '1.1.0' );

// "UTM Source Variants" feature
define( 'CANONICAL_PAGES_UTM_OPTION', 'canonical_pages_utm_source_variants' ); // Option name, '1' when enabled
define( 'CANONICAL_PAGES_UTM_CPT', 'cp_utm_sources' );                         // Post type key (<= 20 chars)
define( 'CANONICAL_PAGES_UTM_META', '_canonical_utm_variants' );               // Post meta on a UTM Source record
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
