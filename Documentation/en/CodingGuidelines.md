# Coding Guidelines (Summary)

This document summarizes the most important coding conventions used in the OT SiteKit ecosystem.
It is a condensed version of the internal SSoT guidelines.

---

## Core Principles

- Separation of concerns:
    - CSS = styling
    - JavaScript = behaviour
- No coupling between CSS and JavaScript
- Use Bootstrap 5 as foundation
- Prefer Vanilla JavaScript
- No jQuery
- No BEM

---

## CSS / SCSS

### Architecture

- Pragmatic CUBE CSS approach
- Atomic Design (Atom → Molecule → Organism → Template → Page)
- Bootstrap-first strategy

### Naming

- Classes: `kebab-case`
- IDs: `lowerCamelCase`

### Prefix System

| Prefix       | Scope                   | Example                  |
|--------------|--------------------------|--------------------------|
| *(none)*     | Bootstrap                | `card`, `btn`            |
| `sk-`        | SiteKit Base             | `sk-stage`               |
| `{ext-key}-` | TYPO3 Extension          | `ot-gallery-figure`      |
| `cb-`        | ContentBlock             | `cb-price-card-header`   |

### Rules

- No BEM (`__`, `--`)
- No IDs for styling
- Flat class structure
- Max. 2 levels nesting

### Variants

- Bootstrap: `data-bs-theme`
- CMS: `data-variant`
- Structural: additional class

### States

- `is-*`, `has-*`
- Set by JS, styled by CSS

### CSS Variables

- Global: `--sk-*` (defined in `:root`)
- Component: `--{ext-key}-*`

---

## JavaScript

### Principles

- Use `data-js` as the only JS selector
- Never use CSS classes as JS hooks
- Avoid IDs unless necessary

### Naming

- `data-js`: `lowerCamelCase`
- Variables must match attribute value

```html
<button data-js="menuToggle"></button>
```

```js
const menuToggle = document.querySelector('[data-js="menuToggle"]')
```

### Selector Priority

1. `data-js`
2. ID (only if justified)
3. Never CSS classes

### Event Handling

- Use `addEventListener`
- No inline JS

### Initialization

- Only after DOM ready
- Always guard missing elements

### Bootstrap JS

Use native attributes:

```html
<button data-bs-toggle="modal"></button>
```

### Vue Usage

Use Vue only for complex stateful UIs.

---

## File Naming

- Files & directories: `UpperCamelCase`
- Exceptions:
    - `composer.json`
    - `package.json`
    - `webpack.config.js`

---

## Quick Reference

| What              | Convention                 | Example                  |
|-------------------|---------------------------|--------------------------|
| CSS class         | `kebab-case`              | `ot-gallery-figure`      |
| ID                | `lowerCamelCase`          | `productFilterApp`       |
| JS hook           | `data-js`                 | `data-js="menuToggle"`   |
| State class       | `is-*`, `has-*`           | `is-open`                |
| Global CSS var    | `--sk-*`                  | `--sk-color-primary`     |
| Component CSS var | `--{ext-key}-*`           | `--ot-gallery-ratio`     |
| File name         | `UpperCamelCase`          | `ProductFilterApp.js`    |
