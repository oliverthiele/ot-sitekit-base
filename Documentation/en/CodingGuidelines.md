# Coding Guidelines

This document defines naming conventions and coding standards for all packages
in this
monorepo. The goal is one consistent language across HTML, CSS, SCSS, JavaScript
and
Fluid templates — so that a single IDE search always finds all related code.

## Architecture philosophy

We use a **pragmatic CUBE CSS approach** on top of Bootstrap 5:

- **Atomic Design** for component organisation (Atom → Molecule → Organism →
  Template → Page)
- **CUBE CSS thinking** for CSS structure (Composition / Utility / Block /
  Exception)
- **Bootstrap 5** as the layout, utility and base-component foundation
- No BEM — Bootstrap already provides a consistent class grammar; adding BEM on
  top creates a second, competing system

---

## CSS classes

### General format

```css
kebab-case
```

### Prefix system

The prefix immediately tells you *where* the SCSS file lives.

| Prefix       | Scope                              | Example                                       |
|--------------|------------------------------------|-----------------------------------------------|
| *(none)*     | Bootstrap utilities and components | `card`, `btn`, `d-flex`                       |
| `sk-`        | SiteKit-Base package               | `sk-stage`, `sk-breadcrumb-item`              |
| `{ext-key}-` | Custom TYPO3 extension             | `ot-gallery-figure`, `ot-markdown-wrapper`    |
| `cb-`        | TYPO3 ContentBlock element         | `cb-price-card-header`, `cb-hero-stage-media` |

### Inner elements — no BEM double-underscore

Inner elements get the full prefix, separated by a single hyphen. No `__`.

```html
<!-- Correct -->
<figure class="ot-gallery-figure">
    <div class="ot-gallery-media">
        <img class="ot-gallery-img">
    </div>
    <figcaption class="ot-gallery-caption"></figcaption>
</figure>

<!-- Wrong — BEM double-underscore -->
<figure class="ot-gallery__figure">
    <div class="ot-gallery__media">
```

### Variants and modifiers — no BEM double-dash

Do not use BEM modifier syntax (`block--modifier`). Use the following patterns
instead,
depending on the source of the variant:

```html
<!-- 1. Dark / light colour scheme → Bootstrap native mechanism -->
<nav class="sk-main-nav" data-bs-theme="dark">

    <!-- 2. CMS-configurable variant (from FlexForm / TypoScript) → data-variant -->
    <!--    The FlexForm value is passed directly — no class mapping needed in PHP/Fluid -->
    <div class="cb-price-card" data-variant="featured">

        <!-- 3. Always-combined structural variant → additional flat class -->
        <div class="ot-hero-stage ot-hero-stage-fullwidth">
```

### State classes

States that are **set by JavaScript** and **styled by CSS** use the `is-` or
`has-` prefix.
CSS never sets these; JavaScript only adds or removes them.

```html

<div class="sk-accordion" data-js="accordion">
    <div class="sk-accordion-panel is-open">
        <nav class="sk-main-nav has-submenu">
```

```scss
.sk-accordion-panel {
    display: none;

    &.is-open {
        display: block;
    }
}
```

---

## CSS custom properties

Two levels — global design tokens and component-local variables — use different
prefixes
so their scope is always obvious.

### Global design tokens — `--sk-*`

Defined in SiteKit-Base. Available everywhere. Override Bootstrap tokens here if
needed.

```css
--sk-color-primary: #0066cc

;
--sk-color-text: #1a1a1a

;
--sk-spacing-4:

1
rem

;
--sk-font-heading:

'Inter'
,
sans-serif

;
--sk-border-radius:

0.5
rem

;

;
```

### Component-local variables — `--{ext-key}-*`

Defined and consumed within one extension or ContentBlock only.

```css
/* ot-gallery */
--ot-gallery-ratio:

4
/
3
;
--ot-gallery-rendering: cover

;

/* cb-price-card */
--cb-price-card-highlight-color:
var

(
--sk-color-primary

)
;
```

---

## IDs

- Use `lowerCamelCase`
- IDs are **only** for JavaScript access and anchor links
- **Never** use IDs as CSS selectors — specificity conflicts are hard to debug

```html

<section id="contactForm">
    <div id="mainNavigation">
```

```javascript
// Correct — consistent with the HTML attribute value
const form = document.getElementById('contactForm');
```

---

## JavaScript integration

### JS hooks — `data-js`

JavaScript always selects elements via `data-js`, never via CSS classes or IDs.
This decouples layout from behaviour: renaming a CSS class never breaks JS, and
removing a `data-js` attribute never affects styling.

The value is written in `lowerCamelCase` — identical to the JavaScript variable
name that references it. This means a single IDE search for `menuToggle` finds
both the HTML attribute and all JavaScript references.

```html
<!-- Correct -->
<button class="sk-nav-toggle" data-js="menuToggle">
    <div class="sk-accordion-panel" data-js="accordionPanel">

        <!-- Wrong — class used as JS hook -->
        <button class="sk-nav-toggle js-menu-toggle">
```

```javascript
// Correct — same string as in the template, single IDE search finds both
const toggleButton = document.querySelector('[data-js="menuToggle"]');

// Wrong — different spelling from the HTML attribute
const toggleButton = document.querySelector('[data-js="menu-toggle"]');
```

### `data-bs-*` attributes

Use Bootstrap's own `data-bs-*` attributes for all Bootstrap JavaScript
components
(dropdowns, modals, collapses, etc.). Do not replicate this with `data-js`.

```html

<button data-bs-toggle="modal" data-bs-target="#confirmDialog">
```

---

## File names and directories

UpperCamelCase for all directories and file names, unless TYPO3 or a tool
requires otherwise.

```
./Directory/SubDirectory/FileName.ext
```

Common exceptions required by TYPO3 or tooling:

- `Configuration/page.tsconfig`
- `config/system/settings.php`
- `composer.json`, `package.json`
- `webpack.config.js`

---

## Quick reference

| What                   | Convention                        | Example                   |
|------------------------|-----------------------------------|---------------------------|
| CSS class              | `kebab-case`                      | `ot-gallery-figure`       |
| SiteKit class          | `sk-{element}`                    | `sk-stage-media`          |
| Extension class        | `{ext-key}-{component}-{element}` | `ot-gallery-caption`      |
| ContentBlock class     | `cb-{blockname}-{element}`        | `cb-price-card-header`    |
| State class            | `is-{state}`, `has-{state}`       | `is-open`, `has-image`    |
| Global CSS variable    | `--sk-{category}-{name}`          | `--sk-color-primary`      |
| Component CSS variable | `--{ext-key}-{name}`              | `--ot-gallery-ratio`      |
| HTML id                | `lowerCamelCase`                  | `contactForm`             |
| JS hook attribute      | `data-js="{lowerCamelCase}"`      | `data-js="menuToggle"`    |
| CMS variant            | `data-variant="{value}"`          | `data-variant="featured"` |
| Theme variant          | `data-bs-theme="{value}"`         | `data-bs-theme="dark"`    |
| File / directory name  | `UpperCamelCase`                  | `PriceCard/config.yaml`   |
