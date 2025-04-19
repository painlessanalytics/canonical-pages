<?php
/**
 * canonicalPages class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class canonicalPages {

    /**
     * Plugin Singleton
     */
    public static function getInstance() {
        if( isset($GLOBALS['canonical_pages_plugin']) && is_object($GLOBALS['canonical_pages_plugin']) )
            return $GLOBALS['canonical_pages_plugin'];

        $GLOBALS['canonical_pages_plugin'] = new canonicalPages();
        return $GLOBALS['canonical_pages_plugin'];
    }

    /**
     * init()
     * 
     * Initialize plugin
     */
    public function init() {

        $this->registerPostMeta();
        add_action('wp_head', array($this, 'wp_head') );
        $this->initFilters();
    }

    /**
     * initFilters()
     */
    public function initFilters() {
        // WordPress native
        add_filter( 'get_canonical_url', array($this, 'filter_get_canonical_url'), 10, 2 );

        // Yoast SEO found
        if( defined('WPSEO_VERSION') ) {
            add_filter( 'wpseo_canonical', array($this, 'filter_wpseo_canonical'), 10, 2 );
        }

        // Rank Math SEO found
        if( function_exists('rank_math') ) {
            add_filter( 'rank_math/frontend/canonical', array($this, 'filter_rank_math_frontend_canonical'), 10 );
        }
        
        // All in one SEO found
        if( defined('AIOSEO_PHP_VERSION_DIR') ) {
            add_filter( 'aioseo_canonical_url', array($this, 'filter_aioseo_canonical_url'), 10 );
        }

        // Slim SEO found
        if( defined('SLIM_SEO_VER') ) {
            add_filter( 'slim_seo_canonical_url', array($this, 'filter_slim_seo_canonical_url'), 10, 2 );
        }
    }

    /**
     * registerPostMeta()
     */
    private function registerPostMeta() {
        register_post_meta(
            '', // All post types
            '_canonical_pages',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'boolean',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            ]
        );
        register_post_meta(
            '', // All post types
            '_canonical_pages_meta',
            [
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'url' => array(
                                'type' => 'string',
                            ),
                            'option'  => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'single'       => true,
                'type'         => 'object',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'sanitize_callback' => array($this,'sanitizeCanonicalPagesMeta')
            ]
        );
    }

    /**
     * wp_head()
     */
    public function wp_head() {
        if( is_home() ) {
            $id = get_queried_object_id();

            if ( 0 === $id ) {
                return;
            }

            $url = wp_get_canonical_url( $id );
            if ( ! empty( $url ) ) {
                echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
            }
        }
    }

    /**
     * sanitizeCanonicalPagesMeta($meta)
     */
    public function sanitizeCanonicalPagesMeta($meta) {
        // $meta = Associative array
        // Looking for keys:
        //   option = this | custom
        if( !empty($meta['option']) ) {
            $meta['option'] = sanitize_text_field($meta['option']);
            switch($meta['option']) {
                case 'this': // Must match exactly
                case 'custom': { // Must match exactly
                    // Good!
                }; break;
                default: $meta['option'] = 'this'; // set it to the default
            }
        }

        // url = Valid-URL
        if( !empty($meta['url']) ) {
            $meta['url'] = sanitize_text_field($meta['url']);
            $meta['url'] = trim($meta['url']); // Get rid of any spaces
        }

        return $meta;
    }

    /**
     * getCanonicalUrl($url, $id)
     */
    public function getCanonicalUrl($url, $id = null) {
        $id = !empty($id) ? $id : get_the_ID();

        // Is this a page we maintain the canonical URL for?
        if( !$this->isCanonicalPage() ) {
            return $url;
        }

        // We don't have a page ID, just roll with the URL provided
        if( empty($id) ) {
            return $url;
        }

        // Do we manage this page?
        if( !in_array( '_canonical_pages', get_post_custom_keys($id) ) ) {
            return $url;
        }

        // Get the settings
        $enabled = get_post_meta($id, '_canonical_pages', true); // Database is either '0' or '1' if setting saved, '' if not present
        $meta = get_post_meta($id, '_canonical_pages_meta', true);

        // User disabled Canonical, lets honor their best wishes
        if( empty($enabled) ) {
            return '';
        }
        
        // User wants to use a custom URL, here is our chance to shine!
        if( !empty($meta['option']) && $meta['option'] == 'custom' && !empty($meta['url']) ) {
            // One last check, make sure the custom value is a valid URL
            if ( filter_var( $meta['url'], FILTER_VALIDATE_URL ) !== FALSE ) {
                $url = $meta['url'];
            }
        }

        // Let someone else transform this URL
        $url = (string) apply_filters( 'canonical_pages_canonical_url', $url, $id );
        return $url;
    }

    /**
     * isCanonicalPage($id=null)
     * 
     * Returns true if this is a page we maintain, false otherwise
     */
    public function isCanonicalPage($id = null) {

        // If we are on the main page or the blog home page
        if( is_singular() || is_home() )
            return true;
    
        return false;
    }

    /**
     * filter_get_canonical_url($url, $post)
     * WordPress native Canonical URL filter
     * 
     * filter: get_canonical_url
     * ref: https://developer.wordpress.org/reference/hooks/get_canonical_url/
     */
    public function filter_get_canonical_url($url, $post) {
        return $this->getCanonicalUrl($url, $post->ID);
    }

    /**
     * filter_wpseo_canonical($url, $presentation = null)
     * 
     * filter: wpseo_canonical
     * ref: https://developer.yoast.com/features/seo-tags/canonical-urls/api/
     */
    public function filter_wpseo_canonical($url, $presentation = null) {
        $id = !empty($presentation->ID) ? $presentation->ID : get_the_ID();
        return $this->getCanonicalUrl($url, $id);
    }

    /**
     * filter_rank_math_frontend_canonical($url)
     * 
     * filter: rank_math/frontend/canonical
     * ref: https://developer.yoast.com/features/seo-tags/canonical-urls/api/
     */
    public function filter_rank_math_frontend_canonical($url) {
        return $this->getCanonicalUrl($url);
    }

    /**
     * filter_aioseo_canonical_url($url)
     * 
     * filter: aioseo_canonical_url
     * ref: https://aioseo.com/docs/aioseo_canonical_url/
     */
    public function filter_aioseo_canonical_url($url) {
        return $this->getCanonicalUrl($url);
    }

    /**
     * filter_slim_seo_canonical_url($url, $id = null)
     * 
     * filter: slim_seo_canonical_url
     * ref: https://docs.wpslimseo.com/slim-seo/hooks/
     */
    public function filter_slim_seo_canonical_url($url, $id = null) {
        return $this->getCanonicalUrl($url, $id);
    }
};

// eof