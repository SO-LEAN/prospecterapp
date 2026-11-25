import { act, renderHook } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { type Theme, usePreferences, useThemeClass } from './preferences'

const LIGHT_BAR = '#f8fafc'
const DARK_BAR = '#0f1117'

function aSystem(dark: boolean) {
  return () => ({
    matches: dark,
    addEventListener: () => {},
    removeEventListener: () => {},
  })
}

function aSystemThatTurns() {
  const listeners = new Set<() => void>()
  let dark = false
  return {
    matchMedia: () => ({
      get matches() {
        return dark
      },
      addEventListener: (_: string, listener: () => void) => listeners.add(listener),
      removeEventListener: (_: string, listener: () => void) => listeners.delete(listener),
    }),
    turnDark() {
      dark = true
      for (const listener of listeners) {
        listener()
      }
    },
  }
}

function metaContent(name: string) {
  return document.head.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)?.content
}

function onASystem(dark: boolean, theme: Theme) {
  vi.stubGlobal('matchMedia', aSystem(dark))
  act(() => usePreferences.setState({ theme }))
  renderHook(() => useThemeClass())
}

describe('useThemeClass', () => {
  beforeEach(() => {
    document.head.innerHTML =
      '<meta name="color-scheme" content="light dark"><meta name="theme-color" content="">'
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    document.documentElement.classList.remove('dark')
  })

  it("gives the native controls and the browser's bar the chosen light theme, on a dark system", () => {
    onASystem(true, 'light')

    expect(document.documentElement).not.toHaveClass('dark')
    expect(metaContent('color-scheme')).toBe('light')
    expect(metaContent('theme-color')).toBe(LIGHT_BAR)
  })

  it("gives the native controls and the browser's bar the chosen dark theme, on a light system", () => {
    onASystem(false, 'dark')

    expect(document.documentElement).toHaveClass('dark')
    expect(metaContent('color-scheme')).toBe('dark')
    expect(metaContent('theme-color')).toBe(DARK_BAR)
  })

  it('follows the system that turns dark, with the system theme chosen, without reloading the page', () => {
    const system = aSystemThatTurns()
    vi.stubGlobal('matchMedia', system.matchMedia)
    act(() => usePreferences.setState({ theme: 'system' }))
    renderHook(() => useThemeClass())

    act(() => system.turnDark())

    expect(document.documentElement).toHaveClass('dark')
    expect(metaContent('color-scheme')).toBe('dark')
    expect(metaContent('theme-color')).toBe(DARK_BAR)
  })

  it('still gives the page the theme chosen when the page holds none of these metas', () => {
    document.head.innerHTML = ''

    onASystem(false, 'dark')

    expect(document.documentElement).toHaveClass('dark')
  })
})
