# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-07-31

First tagged release. The extension was developed and tested in a small team
before this point and was consumed from its `develop` branch; earlier states
are therefore not covered by version numbers.

### Added

- `ext_emconf.php`, so the extension reports title, description and version in
  the Extension Manager like every other SiteKit package
- German translation for the `mainNavigation` label

### Changed

- Requires TYPO3 `^14.3` and PHP `>=8.4`; support for TYPO3 v13 is dropped
- Requires `oliverthiele/ot-icons ^3.0`
- All language files migrated from XLIFF 1.2 to XLIFF 2.0. Unit identifiers and
  translations are unchanged
- Labels are referenced via translation domain mapping instead of full file
  paths: `ot_sitekitbase.db:` and `frontend.ttc:` replace the verbose
  `LLL:EXT:` references in the TCA, the page TSconfig and the backend previews

### Fixed

- The `mainNavigation` label was referenced by both main menu templates but was
  missing from the language files, so the `aria-label` of the navigation
  rendered empty (contributed by @morange)
- The backend preview for list content elements referenced
  `EXT:ot_sitekitbase/Resouces/…` — a misspelled path that does not exist, so
  the label never resolved
- Six `<source>` texts differed between the English and German language files.
  XLIFF expects them to be identical; the German side is now aligned without
  touching any translation
- Removed the TYPO3 v13 compatibility branch in `GenericPreviewRenderer`, which
  normalised `getRecord()` between array and `RecordInterface`, and two
  redundant `instanceof Folder` checks in `VideoProcessor` that the declared
  return types already guarantee

[1.0.0]: https://github.com/oliverthiele/ot-sitekit-base/releases/tag/v1.0.0
