# Backend Layouts


## Mitgelieferte Layouts:

### Standard

Standardlayout mit optionalem Hero-Bild (volle Breite) und einer Spalte mit einer
maximalen Breite von 1400 px für den Inhalt.

### Small Content

Layout mit optionalem Hero-Bild (volle Breite) und einer kleinen Spalte (8/12) für Inhalte

### Homepage

Auf dieser Seite wird gegebenenfalls der Inhalt für den Seiten-Footer gepflegt.

### Advanced

Es gibt hier nur eine Spalte, in der über Container das Layout definiert wird.
So kann z. B. der Inhalt mal über die gesamte Bildschirmbreite und dann wieder
nur über max. 1400 Pixel Breite gehen. Dies ist für den Redakteur mit mehr
Aufwand verbunden, aber dafür ist man hier sehr viel flexibler

## Einbindung in das eigene Sitepackage

Einbinden aller Backend Layouts mit:

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
