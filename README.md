# ot-sitekit-base

Requirements:

- TYPO3 v13

Basic extension for TYPO3 for a kind of modular system for websites.

This extension is designed to quickly create accessible and
search engine optimised websites. In contrast to the extension
‘Bootstrap Package’, for example, not everything is in one package, but
distributed across many small extensions, which can then be integrated per
installation, but also omitted. Everything was either rebuilt with TYPO3 v13
Feature or adapted accordingly.

The most important features for the extensions:

- All extensions for the site kit are kept compatible with each other
- Each extension uses the site sets added in TYPO3 v13
- Each content element is a separate extension
- Each content element uses fields that are also used by other extensions, so
  that switching between CTypes is easier and the database does not
  unnecessarily get new fields all the time.
- Content elements supplied by TYPO3 will only be activated after they have been
  revised and checked for accessibility, best practice, SEO and responsiveness.
