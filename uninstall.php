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
        // Uninstall stuff here
    }
}

new canonicalPagesUninstall();

// eof