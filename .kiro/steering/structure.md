# Project Structure

## Root Level
```
canonical-pages/
├── canonical-pages.php              # Main plugin file (entry point)
├── canonical-pages.class.php        # Core plugin logic
├── uninstall.php                    # Cleanup on plugin deletion
├── admin/                           # Admin/editor functionality
├── .wordpress-org/                  # WordPress.org assets
└── .kiro/                          # Kiro configuration
```

## Key Files

### Plugin Entry Point
- `canonical-pages.php`: Defines constants, loads classes, registers hooks
- Checks for `ABSPATH` to prevent direct access
- Initializes plugin on `init` action

### Core Classes
- `canonical-pages.class.php`: Main plugin class (`canonicalPages`)
  - Singleton pattern via `getInstance()`
  - Registers post meta fields
  - Handles canonical URL logic
  - Integrates with SEO plugin filters
  
- `admin/canonical-pages-admin.class.php`: Admin class (`canonicalPagesAdmin`)
  - Singleton pattern via `getInstance()`
  - Enqueues block editor assets
  - Loaded only in admin context

### Admin Directory
```
admin/
├── canonical-pages-admin.class.php  # Admin functionality
├── edit.js                          # React source (block editor)
├── edit.min.js                      # Compiled JavaScript
├── package.json                     # npm dependencies
├── webpack.config.js                # Build configuration
└── .babelrc                         # Babel configuration
```

## Code Organization Patterns

### Class Structure
- Use singleton pattern for main classes
- Store instances in `$GLOBALS` array
- Prefix: `canonical_pages_` for functions/globals
- Class names: camelCase (e.g., `canonicalPages`, `canonicalPagesAdmin`)

### WordPress Integration
- Post meta keys: `_canonical_pages` (boolean), `_canonical_pages_meta` (object)
- Filter hooks: `get_canonical_url`, `wpseo_canonical`, `rank_math/frontend/canonical`, etc.
- Action hooks: `init`, `wp_head`, `enqueue_block_editor_assets`

### Security
- Always check `ABSPATH` at file start
- Use `current_user_can('edit_posts')` for auth callbacks
- Sanitize with `sanitize_text_field()` and `esc_url()`
- Validate URLs with `filter_var($url, FILTER_VALIDATE_URL)`

## File Naming Conventions
- PHP classes: `{name}.class.php`
- Main plugin file: `{plugin-slug}.php`
- Compiled JS: `{name}.min.js`
