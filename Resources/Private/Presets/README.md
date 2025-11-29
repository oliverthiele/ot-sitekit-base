# Presets

Presets contain folders that are neither genuine "Fluid components"
nor contain pure HTML files.


### Example folder structure

```
Resources/Private/Presets
 ├── Bootstrap5
 │    └── Partials
 │         └── Navigation
 │             ├── Index.html
 │             ├── Index.js
 │             └── Index.scss
```

### JS Import

```js
import { initNavbarAdvanced } from '@sitekit-presets/Bootstrap5/Partials/Navigation/Main/Advanced/Index.js';
import { initLanguageMenuDropdown } from '@sitekit-presets/Bootstrap5/Partials/Navigation/LanguageMenu/Dropdown/Index.js';
import { initColorModeDropdown } from '@sitekit-presets/Bootstrap5/Partials/Features/ColorMode/Default/Index.js';
```

### CSS Import

```scss
@import "@sitekit-presets/Bootstrap5/Partials/Navigation/Main/Advanced/Index.scss";
@import "@sitekit-presets/Bootstrap5/Partials/Navigation/LanguageMenu/Dropdown/Index.scss";
@import "@sitekit-presets/Bootstrap5/Partials/Features/ColorMode/Default/Index.scss";
```

`@sitekit-presets` can only work if the frontend build can resolve the path,
even though the Sitekit extension is not in the `node_modules` folder.

I use this webpack configuration for this purpose:

```js
    resolve: {
      symlinks: false,
      alias: {
        '@sitekit-presets': path.resolve(__dirname, '../../vendor/oliverthiele/ot-sitekit-base/Resources/Private/Presets'),
      },
      modules: [
        // Prioritised: Build/Default/node_modules
        path.resolve(process.cwd(), 'node_modules'),
        'node_modules' // Standard Fallback
      ],
    },
```
