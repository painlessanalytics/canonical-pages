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
                [ 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-data', 'wp-core-data' ],
                CANONICAL_PAGES_VERSION,
                true
            );

            // Tell the sidebar whether the UTM Source Variants feature is enabled
            wp_localize_script(
                'canonical-pages-edit',
                'canonicalPagesData',
                array(
                    'utmVariantsEnabled' => canonicalPages::getInstance()->isUtmVariantsEnabled(),
                    'utmCpt'             => CANONICAL_PAGES_UTM_CPT,
                )
            );
        });

        // Plugin settings page
        add_action( 'admin_menu', array( $this, 'registerSettingsPage' ) );
        add_action( 'admin_init', array( $this, 'registerSettings' ) );

        // Canonical UTM Sources editing (only when the feature is enabled)
        if( canonicalPages::getInstance()->isUtmVariantsEnabled() ) {
            add_action( 'add_meta_boxes', array( $this, 'registerUtmSourceMetaBox' ) );
            add_action( 'save_post_' . CANONICAL_PAGES_UTM_CPT, array( $this, 'saveUtmSourceMetaBox' ), 10, 2 );

            // Keep the simple textarea editor (classic) for UTM Source records
            add_filter( 'use_block_editor_for_post_type', function( $use, $post_type ) {
                return ( $post_type === CANONICAL_PAGES_UTM_CPT ) ? false : $use;
            }, 10, 2 );
        }
    }

    /**
     * registerSettingsPage()
     */
    public function registerSettingsPage() {
        add_options_page(
            __( 'Canonical Pages', 'canonical-pages' ),
            __( 'Canonical Pages', 'canonical-pages' ),
            'manage_options',
            'canonical-pages',
            array( $this, 'renderSettingsPage' )
        );
    }

    /**
     * registerSettings()
     */
    public function registerSettings() {
        register_setting(
            'canonical_pages_settings',
            CANONICAL_PAGES_UTM_OPTION,
            array(
                'type'              => 'boolean',
                'sanitize_callback' => array( $this, 'sanitizeUtmOption' ),
                'default'           => false,
            )
        );
    }

    /**
     * sanitizeUtmOption($value)
     */
    public function sanitizeUtmOption( $value ) {
        return ! empty( $value ) ? '1' : '';
    }

    /**
     * renderSettingsPage()
     */
    public function renderSettingsPage() {
        if( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $enabled = (bool) get_option( CANONICAL_PAGES_UTM_OPTION, false );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'canonical_pages_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'UTM Source Variants', 'canonical-pages' ); ?></th>
                        <td>
                            <label for="<?php echo esc_attr( CANONICAL_PAGES_UTM_OPTION ); ?>">
                                <input
                                    type="checkbox"
                                    id="<?php echo esc_attr( CANONICAL_PAGES_UTM_OPTION ); ?>"
                                    name="<?php echo esc_attr( CANONICAL_PAGES_UTM_OPTION ); ?>"
                                    value="1"
                                    <?php checked( $enabled ); ?>
                                />
                                <?php esc_html_e( 'Variant Canonical Pages to UTM Sources', 'canonical-pages' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'When enabled, you can manage lists of "Canonical UTM Sources" and map a list to any page that uses the "This Link" canonical option. Each value in the list becomes an additional path that loads the same page. For example, if the page is example.com/pizza/ and the list contains "pepperoni" and "mushroom", then example.com/pizza/pepperoni/ and example.com/pizza/mushroom/ will load the pizza page while keeping example.com/pizza/ as the canonical URL. The matched value is also exposed to analytics as a UTM source.', 'canonical-pages' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Settings', 'canonical-pages' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * registerUtmSourceMetaBox()
     */
    public function registerUtmSourceMetaBox() {
        add_meta_box(
            'canonical-utm-source-values',
            __( 'UTM Source Values', 'canonical-pages' ),
            array( $this, 'renderUtmSourceMetaBox' ),
            CANONICAL_PAGES_UTM_CPT,
            'normal',
            'high'
        );
    }

    /**
     * renderUtmSourceMetaBox($post)
     */
    public function renderUtmSourceMetaBox( $post ) {
        $slugs = get_post_meta( $post->ID, CANONICAL_PAGES_UTM_META, true );
        $value = is_array( $slugs ) ? implode( "\n", $slugs ) : '';

        wp_nonce_field( 'canonical_utm_source_save', 'canonical_utm_source_nonce' );
        ?>
        <p>
            <label for="canonical_utm_source_values">
                <?php esc_html_e( 'Enter one value per line. Each value is converted to a lowercase, slug-compatible value.', 'canonical-pages' ); ?>
            </label>
        </p>
        <textarea
            id="canonical_utm_source_values"
            name="canonical_utm_source_values"
            rows="10"
            class="large-text code"
            placeholder="pepperoni&#10;mushroom"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <?php
    }

    /**
     * saveUtmSourceMetaBox($post_id, $post)
     */
    public function saveUtmSourceMetaBox( $post_id, $post ) {
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if( ! isset( $_POST['canonical_utm_source_nonce'] )
            || ! wp_verify_nonce( $_POST['canonical_utm_source_nonce'], 'canonical_utm_source_save' ) ) {
            return;
        }
        if( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if( ! isset( $_POST['canonical_utm_source_values'] ) ) {
            return;
        }

        $raw   = wp_unslash( $_POST['canonical_utm_source_values'] );
        $lines = preg_split( '/\r\n|\r|\n/', $raw );
        $slugs = array();

        foreach( $lines as $line ) {
            $slug = sanitize_title( $line );
            if( $slug !== '' && ! in_array( $slug, $slugs, true ) ) {
                $slugs[] = $slug;
            }
        }

        update_post_meta( $post_id, CANONICAL_PAGES_UTM_META, $slugs );
    }


};
 
canonicalPagesAdmin::getInstance()->start();
 
 // eof