# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes verified changes between its release tag and the immediately preceding published release. Changes made later on `master` are excluded.

## 1.0.9 - 2026-10-15

### Changed

- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, including revised startup environment variables and health-check configuration.
- Added the plugin icon at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.8` to `1.0.9` and its build number from `2026101200` to `2026101500`.

No functional changes to the Video Reflection activity were identified between the published `1.0.8` and `1.0.9` tags.

[Compare 1.0.8...1.0.9](https://github.com/EduardoKrausME/moodle-mod_videoreflection/compare/1.0.8...1.0.9)

## 1.0.8 - 2026-10-12

### Fixed

- Hardened the GitHub Actions release workflow against shell injection by passing the head commit message through the `RELEASE_MESSAGE` environment variable instead of interpolating it directly into shell commands.

### Changed

- Updated the PHP 8.3 CI matrix from Moodle 5.2 (`MOODLE_502_STABLE`) to Moodle 5.3 (`MOODLE_503_STABLE`), retaining the Moodle 4.5 test entry.
- Updated the plugin release identifier from `1.0.7` to `1.0.8` and its build number from `2026100503` to `2026101200`.

No functional changes to the Video Reflection activity were identified between the published `1.0.7` and `1.0.8` tags.

[Compare 1.0.7...1.0.8](https://github.com/EduardoKrausME/moodle-mod_videoreflection/compare/1.0.7...1.0.8)
