# Technology Stack

## Core Technologies
- **PHP**: 7.4+ (WordPress plugin backend)
- **JavaScript**: ES6+ with React (Block editor integration)
- **WordPress**: 6.0+ (tested up to 6.9)

## Build System
- **Webpack 4**: Module bundler for JavaScript
- **Babel**: JavaScript transpiler with React preset
- **npm**: Package manager

## WordPress APIs Used
- Block Editor (Gutenberg) APIs
- Post Meta API with REST support
- WordPress Hooks (actions and filters)
- Singleton pattern for class instantiation

## Common Commands

### Development
```bash
cd admin
npm install              # Install dependencies
npm run watch           # Watch mode for development
npm run build           # Production build (minified)
```

### Build Output
- Source: `admin/edit.js`
- Compiled: `admin/edit.min.js`

## Dependencies
- `@wordpress/i18n`: Internationalization
- `@wordpress/element`: React wrapper
- `@wordpress/blocks`: Block editor APIs
- `@wordpress/components`: UI components
- `@wordpress/editor`: Editor utilities

## Plugin Constants
- `CANONICAL_PAGES_PLUGIN_PATH`: Plugin directory path
- `CANONICAL_PAGES_PLUGIN_URL`: Plugin URL
- `CANONICAL_PAGES_VERSION`: Current version (1.0.1)
