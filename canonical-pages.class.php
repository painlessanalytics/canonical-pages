<?php
/**
 * canonicalPages class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class canonicalPages {

    /**
     * Matched UTM Source slug for the current variant request (null when not a variant)
     */
    private $variantUtmSource = null;

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

        // "UTM Source Variants" feature (only when enabled in settings)
        if( $this->isUtmVariantsEnabled() ) {
            $this->registerUtmSourcesPostType();
            add_action('template_redirect', array($this, 'maybeServeVariant'), 10 );
            add_action('wp_head', array($this, 'printVariantUtmSource'), 1 );
        }
    }

    /**
     * isUtmVariantsEnabled()
     *
     * True when the "Variant Canonical Pages to UTM Sources" setting is on.
     */
    public function isUtmVariantsEnabled() {
        return (bool) get_option( CANONICAL_PAGES_UTM_OPTION, false );
    }

    /**
     * registerUtmSourcesPostType()
     *
     * Custom post type used to manage lists of "Canonical UTM Sources".
     * Admin-only container (not publicly queryable) but exposed to REST so the
     * block editor sidebar can list the published records in a dropdown.
     */
    public function registerUtmSourcesPostType() {
        register_post_type( CANONICAL_PAGES_UTM_CPT, array(
            'labels' => array(
                'name'               => __( 'Canonical UTM Sources', 'canonical-pages' ),
                'singular_name'      => __( 'Canonical UTM Source', 'canonical-pages' ),
                'menu_name'          => __( 'Canonical Pages', 'canonical-pages' ),
                'add_new'            => __( 'Add New Source', 'canonical-pages' ),
                'add_new_item'       => __( 'Add New Source', 'canonical-pages' ),
                'edit_item'          => __( 'Edit Canonical UTM Source', 'canonical-pages' ),
                'new_item'           => __( 'New Canonical UTM Source', 'canonical-pages' ),
                'view_item'          => __( 'View Canonical UTM Source', 'canonical-pages' ),
                'search_items'       => __( 'Search Canonical UTM Sources', 'canonical-pages' ),
                'not_found'          => __( 'No Canonical UTM Sources found', 'canonical-pages' ),
                'all_items'          => __( 'UTM Sources', 'canonical-pages' ),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true, // Own top-level "Canonical Pages" menu (UTM Sources, Add New Source)
            'show_in_rest'        => true,
            'rest_base'           => CANONICAL_PAGES_UTM_CPT,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-admin-links',
            'menu_position'       => 80,
            'rewrite'             => array( 'slug' => 'canonical-utm-sources' ),
            'supports'            => array( 'title' ),
            'capability_type'     => 'post',
        ) );
    }

    /**
     * getVariantSlugs($listId)
     *
     * Returns the array of sanitized slugs stored on a Canonical UTM Source record.
     */
    public function getVariantSlugs($listId) {
        $slugs = get_post_meta( $listId, CANONICAL_PAGES_UTM_META, true );
        return is_array($slugs) ? $slugs : array();
    }

    /**
     * maybeServeVariant()
     *
     * When WordPress fails to resolve a request, try treating the final path
     * segment as a UTM Source variant of the parent page. If the parent page
     * uses "This Link" and is mapped to a UTM Source list that contains the
     * segment, serve the parent page's content (its canonical URL stays the
     * parent permalink) and remember the matched segment for analytics output.
     *
     * Example: example.com/pizza/pepperoni/ serves example.com/pizza/ when
     * "pepperoni" is one of the mapped UTM Source slugs.
     */
    public function maybeServeVariant() {
        global $wp, $wp_query;

        if( ! is_404() ) {
            return; // Real, resolvable request - leave it alone
        }

        $request = isset($wp->request) ? trim( $wp->request, '/' ) : '';
        if( $request === '' ) {
            return;
        }

        $segments = explode( '/', $request );
        $variant  = sanitize_title( array_pop( $segments ) );
        if( $variant === '' ) {
            return;
        }

        // Resolve the parent (base) post from the remaining path
        $basePath = implode( '/', $segments );
        $baseUrl  = home_url( $basePath === '' ? '/' : '/' . $basePath . '/' );
        $baseId   = url_to_postid( $baseUrl );
        if( ! $baseId ) {
            return;
        }

        // The base page must have Canonical enabled and set to "This Link"
        $enabled = get_post_meta( $baseId, '_canonical_pages', true );
        if( empty($enabled) ) {
            return;
        }

        $meta = get_post_meta( $baseId, '_canonical_pages_meta', true );
        if( empty($meta['option']) || $meta['option'] !== 'this' || empty($meta['variant']) ) {
            return;
        }

        // The matched segment must be one of the mapped UTM Source slugs
        $variants = $this->getVariantSlugs( (int) $meta['variant'] );
        if( ! in_array( $variant, $variants, true ) ) {
            return;
        }

        $post = get_post( $baseId );
        if( ! $post ) {
            return;
        }

        // Re-run the main query for the base post so the correct template loads
        if( $post->post_type === 'page' ) {
            $args = array( 'page_id' => $baseId );
        } else {
            $args = array( 'p' => $baseId, 'post_type' => $post->post_type );
        }
        $args['posts_per_page'] = 1;

        $new_query = new WP_Query( $args );
        if( ! $new_query->have_posts() ) {
            return;
        }

        // Point the main query, the canonical query, and the post globals at
        // the base post so template tags and SEO plugins resolve correctly.
        $wp_query                      = $new_query;
        $GLOBALS['wp_the_query']       = $new_query;
        $GLOBALS['post']               = get_post( $baseId );
        setup_postdata( $GLOBALS['post'] );
        status_header( 200 );

        $this->variantUtmSource = $variant;
    }

    /**
     * printVariantUtmSource()
     *
     * Exposes the matched variant segment to client-side analytics as a
     * window.utm_source value and a dataLayer push.
     */
    public function printVariantUtmSource() {
        if( empty($this->variantUtmSource) ) {
            return;
        }

        $source = esc_js( $this->variantUtmSource );
        echo "<script>window.utm_source=window.utm_source||'" . $source . "';"
            . "window.dataLayer=window.dataLayer||[];"
            . "window.dataLayer.push({'utm_source':'" . $source . "'});</script>\n";
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
                'default'      => true, // Canonical enabled by default; lets an explicit "false" persist
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
                            'variant'  => array(
                                'type' => 'integer',
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

        // variant = ID of a Canonical UTM Source record (0 = none)
        if( isset($meta['variant']) ) {
            $meta['variant'] = (int) $meta['variant'];
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