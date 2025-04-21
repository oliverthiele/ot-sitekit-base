(() => {
  'use strict'

  const dropdownToggle = document.getElementById('languageMenuDropdown')
  const dropdownMenu = document.getElementById('language-navigation')
  const items = dropdownMenu?.querySelectorAll('.dropdown-item:not([aria-disabled="true"])')

  if (!dropdownToggle || !dropdownMenu || !items?.length) return

  let currentIndex = 0

  // Öffnet Menü mit Tastatur (Pfeil ↓ oder Space)
  dropdownToggle.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown' || event.key === ' ') {
      event.preventDefault()
      dropdownToggle.setAttribute('aria-expanded', 'true')
      dropdownToggle.classList.add('show')
      dropdownMenu.classList.add('show')
      items[0].focus()
    }
  })

  // Navigation innerhalb des Menüs
  dropdownMenu.addEventListener('keydown', (event) => {
    const maxIndex = items.length - 1

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault()
        currentIndex = (currentIndex + 1 > maxIndex) ? 0 : currentIndex + 1
        items[currentIndex].focus()
        break
      case 'ArrowUp':
        event.preventDefault()
        currentIndex = (currentIndex - 1 < 0) ? maxIndex : currentIndex - 1
        items[currentIndex].focus()
        break
      case 'Escape':
        dropdownToggle.setAttribute('aria-expanded', 'false')
        dropdownToggle.classList.remove('show')
        dropdownMenu.classList.remove('show')
        dropdownToggle.focus()
        break
      case 'Enter':
      case ' ':
        if (document.activeElement?.tagName === 'A' && document.activeElement.href) {
          window.location.href = document.activeElement.href
        }
        break
    }
  })

  // Fokus beim Öffnen zurücksetzen
  dropdownToggle.addEventListener('click', () => {
    currentIndex = 0
  })
})()
