import * as bootstrap from 'bootstrap';

export function initNavbarAdvanced() {

    // For every navigation area (may occur more than once)
    document.querySelectorAll('[data-js="mainMenuList"]').forEach(navbar => {
      const focusableSelectors = '[data-js="mainMenuItem"]';

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

        // Case: focus is inside a dropdown menu (e.g. on .dropdown-item)
        const isInDropdown = event.target.closest('.dropdown-menu');
        if (isInDropdown) {
          const toggleButtonId = isInDropdown.getAttribute('aria-labelledby');
          const toggleButton = document.getElementById(toggleButtonId);

          // Close the dropdown
          const bsInstance = bootstrap.Dropdown.getInstance(toggleButton);
          if (bsInstance) {
            bsInstance.hide();
          }

          // Move focus within the main menu
          const items = getFocusableItems();
          const currentIndex = items.indexOf(toggleButton);
          const newIndex = currentIndex + direction;

          if (newIndex >= 0 && newIndex < items.length) {
            event.preventDefault();
            items[newIndex].focus();
          }

          return; // early exit
        }

        // Default focus navigation on the top level
        const items = getFocusableItems();
        const currentIndex = items.indexOf(document.activeElement);
        if (currentIndex === -1) return;

        const newIndex = currentIndex + direction;

        if (newIndex < 0 || newIndex >= items.length) {
          event.preventDefault(); // stop at the edge
          return;
        }

        event.preventDefault();
        items[newIndex].focus();
      });
    });

    // Dropdown focus when opening (Bootstrap)
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

    // ESC key closes the main menu (#navbarMain)
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