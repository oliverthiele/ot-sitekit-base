# OT SiteKit Base — TYPO3 Modular Site System

A base extension for TYPO3 v13/v14 that provides the foundation for a modular,
scalable and maintainable site
architecture based on TYPO3 SiteSets and small, focused extensions.

> ⚠️ **Early Development Status**
>
> This extension is currently in active development and primarily intended for
> **internal project usage**.
> Breaking changes may occur at any time without prior notice.
> No public API stability or backward compatibility is guaranteed yet.
>
> Use at your own risk. No public support is provided at this stage.

[![TYPO3](https://img.shields.io/badge/TYPO3-14.3-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)

---

## Why OT SiteKit Base?

TYPO3 projects often grow into large, monolithic setups where content elements,
configuration and logic are tightly
coupled. This makes long-term maintenance, upgrades and refactoring increasingly
difficult.

OT SiteKit Base follows a different approach:

- **Strict modularization** — each content element is its own extension
- **Shared field concepts** — consistent data structures across extensions
- **SiteSet-based configuration** — no legacy TypoScript dependency chains
- **Composable architecture** — pick only what a project actually needs

The goal is not to provide a "one-size-fits-all" solution, but a **system
architecture** that scales across projects and
teams.

---

## Core Concepts

- **Small, focused extensions**
    - Each content element is developed and versioned independently
    - Extensions can be combined or omitted per project

- **Consistent field reuse**
    - Shared fields across content elements
    - Easier CType switching without data loss
    - Avoid unnecessary database growth

- **SiteSets as configuration backbone**
    - TYPO3 v13+ native configuration approach
    - No manual TypoScript includes required

- **Controlled activation of TYPO3 core elements**
    - Core content elements are only enabled after review
    - Ensures accessibility, SEO and best practices

---

## Features

- Modular TYPO3 architecture based on independent extensions
- Shared field definitions across content elements
- TYPO3 SiteSet integration (v13+)
- Structured content element ecosystem
- Focus on accessibility, SEO and maintainability
- Designed for long-term project evolution and refactoring

---

## Requirements

| Requirement | Version   |
|-------------|-----------|
| TYPO3       | 14.3      |
| PHP         | 8.4+      |

---

## Installation (Development Only)

**TYPO3 v14 (current development):**

```bash
composer require oliverthiele/ot-sitekit-base:dev-develop
```

**TYPO3 v13 LTS:**

```bash
composer require oliverthiele/ot-sitekit-base:dev-typo3-v13
```
