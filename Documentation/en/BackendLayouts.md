# Backend Layouts


## Mitgelieferte Layouts:

### Standard

Standard layout with optional hero image (full width) and one column with a maximum width of 1400 px for the content

### Small Content

Layout with optional hero image (full width) and one small column (8/12) for content

### Homepage




Einbinden aller Backend Layouts mit

```typo3_typoscript
name: oliverthiele/ot-sitepackage
label: Configuration for customer sitepackage

dependencies:
- oliverthiele/ot-sitekit-base
- oliverthiele/ot-sitekit-base-backend-layouts
#  - oliverthiele/ot-sitekit-base-backend-layout-advanced
#  - oliverthiele/ot-sitekit-base-backend-layout-default
#  - oliverthiele/ot-sitekit-base-backend-layout-homepage
#  - oliverthiele/ot-sitekit-base-backend-layout-small-content
- oliverthiele/ot-sitekit-base-page
- oliverthiele/ot-sitekit-base-content-elements
```

oder einzeln mit

```typo3_typoscript

name: oliverthiele/ot-sitepackage
label: Configuration for customer sitepackage

dependencies:
  - oliverthiele/ot-sitekit-base
#  - oliverthiele/ot-sitekit-base-backend-layouts
  - oliverthiele/ot-sitekit-base-backend-layout-advanced
  - oliverthiele/ot-sitekit-base-backend-layout-default
  - oliverthiele/ot-sitekit-base-backend-layout-homepage
  - oliverthiele/ot-sitekit-base-backend-layout-small-content
  - oliverthiele/ot-sitekit-base-page
  - oliverthiele/ot-sitekit-base-content-elements
```
