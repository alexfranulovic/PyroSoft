/*!
 * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
 * Copyright 2011-2024 The Bootstrap Authors
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 */

(() => {
  'use strict'

  const getStoredTheme = () => {
    const local = localStorage.getItem('theme')
    if (local) return local

    const match = document.cookie.match(/(?:^|;\s*)theme_color=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : null
  }

  const setStoredTheme = theme => {
    localStorage.setItem('theme', theme)
    document.cookie = `theme_color=${encodeURIComponent(theme)}; path=/; max-age=31536000`
  }

  const getPreferredTheme = () => {
    const storedTheme = getStoredTheme()
    if (storedTheme) return storedTheme
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }

  const setTheme = theme => {
    if (theme === 'auto') {
      const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      document.documentElement.setAttribute('data-bs-theme', systemPrefersDark ? 'dark' : 'light')
    } else {
      document.documentElement.setAttribute('data-bs-theme', theme)
    }
  }

  const updateThemeIcon = theme => {
    const themeSwitcherBtn = document.querySelector('.theme-color-mode .dropdown-toggle')
    const activeOption = document.querySelector(`[data-bs-theme-value="${theme}"]`)
    if (!themeSwitcherBtn || !activeOption) return

    const newIcon = activeOption.querySelector('i')?.cloneNode(true)
    const currentIcon = themeSwitcherBtn.querySelector('i')

    if (newIcon && currentIcon) {
      themeSwitcherBtn.replaceChild(newIcon, currentIcon)
    }
  }

  const showActiveTheme = (theme, focus = false) => {
    const themeSwitcher = document.querySelector('.theme-color-mode')
    if (!themeSwitcher) return

    const themeSwitcherText = document.querySelector('.dropdown-toggle')
    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)

    document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
      element.classList.remove('active')
      element.setAttribute('aria-pressed', 'false')
    })

    btnToActive.classList.add('active')
    btnToActive.setAttribute('aria-pressed', 'true')

    const themeSwitcherLabel = `${themeSwitcherText.textContent.trim()} (${btnToActive.dataset.bsThemeValue})`
    themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

    updateThemeIcon(theme)

    if (focus) themeSwitcher.focus()
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme()
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
      setTheme(getPreferredTheme())
    }
  })

  showActiveTheme(getPreferredTheme())

  document.querySelectorAll('[data-bs-theme-value]').forEach(toggle => {
    toggle.addEventListener('click', () => {
      const theme = toggle.getAttribute('data-bs-theme-value')
      setStoredTheme(theme)
      setTheme(theme)
      showActiveTheme(theme, true)
    })
  })
})()
