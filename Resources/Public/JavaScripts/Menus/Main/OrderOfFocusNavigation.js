document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(button => {
  button.addEventListener('shown.bs.dropdown', () => {
    const menuId = button.getAttribute('aria-controls');
    const menu = document.getElementById(menuId);
    const firstItem = menu?.querySelector('[role="menuitem"]');
    if (firstItem) firstItem.focus();
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
