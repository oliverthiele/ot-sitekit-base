// Build/Default/src/js/Components/Navbar/initNavbarAdvanced.js

import * as bootstrap from '../../../../../../../../../../../Build/Default/node_modules/bootstrap';

export function initNavbarAdvanced() {

    // Für alle Navigationsbereiche (kann mehrfach vorkommen)
    document.querySelectorAll('.navbar-nav').forEach(navbar => {
      const focusableSelectors = '.nav-link, .dropdown-toggle-btn';

      const getFocusableItems = () => {
        return Array.from(navbar.querySelectorAll(focusableSelectors))
          .filter(el =>
            typeof el.focus === 'function' &&
            !el.disabled &&
            !el.hasAttribute('aria-disabled') &&
            el.offsetParent !== null
          );
      };

      navbar.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        const direction = event.key === 'ArrowRight' ? 1 : -1;

        // Fall: Fokus ist in einem Dropdown-Menü (z. B. auf .dropdown-item)
        const isInDropdown = event.target.closest('.dropdown-menu');
        if (isInDropdown) {
          const toggleButtonId = isInDropdown.getAttribute('aria-labelledby');
          const toggleButton = document.getElementById(toggleButtonId);

          // Dropdown schließen
          const bsInstance = bootstrap.Dropdown.getInstance(toggleButton);
          if (bsInstance) {
            bsInstance.hide();
          }

          // Fokus verschieben im Hauptmenü
          const items = getFocusableItems();
          const currentIndex = items.indexOf(toggleButton);
          const newIndex = currentIndex + direction;

          if (newIndex >= 0 && newIndex < items.length) {
            event.preventDefault();
            items[newIndex].focus();
          }

          return; // vorzeitiger Exit
        }

        // Standard-Fokus-Navigation auf Top-Ebene
        const items = getFocusableItems();
        const currentIndex = items.indexOf(document.activeElement);
        if (currentIndex === -1) return;

        const newIndex = currentIndex + direction;

        if (newIndex < 0 || newIndex >= items.length) {
          event.preventDefault(); // am Rand stoppen
          return;
        }

        event.preventDefault();
        items[newIndex].focus();
      });
    });

    // Dropdown-Fokus beim Öffnen (Bootstrap)
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(button => {
      button.addEventListener('shown.bs.dropdown', () => {
        const menuId = button.getAttribute('aria-controls');
        const menu = document.getElementById(menuId);
        if (!menu) return;

        const items = Array.from(menu.querySelectorAll('a.dropdown-item'))
          .filter(el =>
            typeof el.focus === 'function' &&
            !el.disabled &&
            !el.hasAttribute('aria-disabled') &&
            el.offsetParent !== null
          );

        if (!items.length) return;

        let targetItem = items.find(item => item.getAttribute('aria-current') !== 'page');
        if (!targetItem) {
          targetItem = items[0];
        }

        targetItem?.focus();
      });
    });




    // ESC-Taste schließt das Hauptmenü (#navbarMain)
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' || event.key === 'Esc') {
        const navbarCollapse = document.getElementById('navbarMain');
        if (navbarCollapse.classList.contains('show')) {
          const collapse = bootstrap.Collapse.getInstance(navbarCollapse);
          if (collapse) {
            collapse.hide();
          }
        }
      }
    });
}
