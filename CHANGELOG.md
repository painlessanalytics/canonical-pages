# Canonical Pages Changelog
Canonical Pages WordPress plugin.

All notable changes to this project will be documented in this file.

**Keep a Changelog**
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

Each version section should start with a H2 (`## `), the version number in hard brackets, a space-dash-space (` - `), 
and the release date in ISO date format (ISO 8601), *YYYY-MM-DD*.
The proceeding line may contain 1 or more sentences describing the purpose of the release.
A blank line is added to separate the heading/paragraph from the list of changes.
Changes are listed, each item prefixed with a minus (-) character. Tabs may be used to indent the list.
A blank line is added to separate the list from the next heading/paragraph.

```markdown
## [0.0.0] - 2025-01-02
This is an example changelog entry.

- Fixed this
  - and this,
  - and this
  - and that
- Added that
- Removed other
```

**Semantic Versioning**
This project adheres _somewhat_ to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). 
The first version of a MAJOR or MINOR release will exclude the second dot followed by zero (`.0`).
For example `2.0` will be used rather than `2.0.0`. Otherwise Semantic Versioning is strictly followed.

## [Unreleased]
TBD

## [0.0.3] - 2025-04-18
Release on WordPress.org

- Added .github workflow actions
- Added .wordpress-org folder for wordpress.org assets

## [0.0.3] - 2025-04-12
Issues addressed for wordpress.org submission review process

- Removed `load_plugin_textdomain()` function call, no longer necessary
- Set "Requires at least:" to 6.0 (was 6.0.9)

## [0.0.2] - 2025-04-07
Bug fixes and added CHANGELOG.md

- Added CHANGELOG.md
- Fixed bug where canonical disabled when no settings saved

## [0.0.1] - 2025-04-06
Hello from Canonical Pages plugin!

- First release of plugin
