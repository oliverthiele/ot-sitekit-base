(() => {
  'use strict'

  const getStoredTheme = () => localStorage.getItem('theme')
  const setStoredTheme = theme => localStorage.setItem('theme', theme)

  const getPreferredTheme = () => {
    const storedTheme = getStoredTheme()
    if (storedTheme) {
      return storedTheme
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }

  const updateAriaPressedAndDescribedBy = (theme) => {
    document.querySelectorAll('[data-bs-theme-value]').forEach(btn => {
      const value = btn.getAttribute('data-bs-theme-value')
      const isActive = document.documentElement.getAttribute('data-bs-theme') === value

      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false')
      btn.classList.toggle('active', isActive)

      // Neu: Button (de)aktivieren
      btn.disabled = isActive

      // Neu: aria-describedby setzen/entfernen
      if (isActive) {
        btn.setAttribute('aria-describedby', 'activeTheme')
      } else {
        btn.removeAttribute('aria-describedby')
      }

      // Neu: Sichtbarkeit des .dropdown-item-label-is-active anpassen
      const checkIcon = btn.querySelector('.dropdown-item-label-is-active')
      if (checkIcon) {
        checkIcon.classList.toggle('d-none', !isActive)
      }
    })

    // Aktualisiere den versteckten Screenreader-Status
    const activeThemeStatus = document.getElementById('activeTheme')
    if (activeThemeStatus) {
      const readable = theme === 'light'
        ? activeThemeStatus?.getAttribute('data-text-light') || 'Aktueller Modus: Hell'
        : activeThemeStatus?.getAttribute('data-text-dark') || 'Aktueller Modus: Dunkel'
      activeThemeStatus.textContent = readable
    }
  }

  const updateActiveIcon = (theme) => {
    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
    const icon = btnToActive?.querySelector('i')
    const activeIconTarget = document.querySelector('.theme-icon-active')

    if (icon && activeIconTarget) {
      activeIconTarget.innerHTML = icon.outerHTML
    }
  }

  const setTheme = theme => {
    if (theme === 'auto') {
      const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
      document.documentElement.setAttribute('data-bs-theme', systemTheme)
      updateAriaPressedAndDescribedBy(systemTheme)
      updateActiveIcon(systemTheme)
    } else {
      document.documentElement.setAttribute('data-bs-theme', theme)
      updateAriaPressedAndDescribedBy(theme)
      updateActiveIcon(theme)
    }
  }

  const showActiveTheme = (theme, focus = false) => {
    const themeSwitcher = document.querySelector('#colorModeDropdown')
    if (!themeSwitcher) {
      return
    }

    const themeSwitcherText = document.querySelector('#bd-theme-text')
    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)

    document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
      element.classList.remove('active')
    })

    btnToActive.classList.add('active')

    // Setze optional aria-label auf dem Umschalter
    const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`
    themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

    if (focus) {
      themeSwitcher.focus()
    }

    updateActiveIcon(theme)
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme()
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
      const preferred = getPreferredTheme()
      setTheme(preferred)
      showActiveTheme(preferred)
    }
  })

  window.addEventListener('DOMContentLoaded', () => {
    const preferred = getPreferredTheme()
    setTheme(preferred)
    showActiveTheme(preferred)

    document.querySelectorAll('[data-bs-theme-value]')
      .forEach(toggle => {
        toggle.addEventListener('click', () => {
          const theme = toggle.getAttribute('data-bs-theme-value')
          setStoredTheme(theme)
          setTheme(theme)
          showActiveTheme(theme, true)
        })
      })
  })
})()
