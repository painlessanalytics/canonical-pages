<?php
/*
* Uninstall for Canonical Pages plugin
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Access denied');
}

/**
 * class canonicalPagesUninstall
 */
class canonicalPagesUninstall
{
    /**
     * canonicalPagesUninstall constructor.
     */
    public function __construct()
    {
        global $wpdb;

        // Remove the "UTM Source Variants" setting
        delete_option( 'canonical_pages_utm_source_variants' );

        // Remove any "Canonical UTM Sources" records and their meta
        $post_ids = $wpdb->get_col(
            $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'cp_utm_sources' )
        );

        foreach ( $post_ids as $post_id ) {
            wp_delete_post( (int) $post_id, true );
        }
    }
}

new canonicalPagesUninstall();

// eof