<?php
/**
 * Canonical Pages WP Admin
 */

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 class canonicalPagesAdmin {
 
    /**
     * Singleton
     */
    public static function getInstance() {
        if( isset($GLOBALS['canonical_pages_plugin_admin']) && is_object($GLOBALS['canonical_pages_plugin_admin']) )
            return $GLOBALS['canonical_pages_plugin_admin'];

        $GLOBALS['canonical_pages_plugin_admin'] = new canonicalPagesAdmin();
        return $GLOBALS['canonical_pages_plugin_admin'];
    }

    /**
     * start()
     */
    public function start() {
        $this->initHooks();
    }

    /**
     * initHooks()
     */
    private function initHooks() {

        add_action( 'enqueue_block_editor_assets', function() {
            wp_enqueue_script(
                'canonical-pages-edit',
                trailingslashit( plugin_dir_url( __FILE__ ) ) . 'edit.min.js',
                [ 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor', 'wp-i18n' ],
                CANONICAL_PAGES_VERSION,
                true
            );
        });
    }
 
     
};
 
canonicalPagesAdmin::getInstance()->start();
 
 // eof