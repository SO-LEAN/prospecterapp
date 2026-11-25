import { useEffect, useSyncExternalStore } from 'react'
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type Theme = 'light' | 'dark' | 'system'

type PreferencesState = {
  theme: Theme
  sidebarCollapsed: boolean
  setTheme: (theme: Theme) => void
  toggleSidebar: () => void
}

/**
 * The UI preferences that only the client knows, persisted in localStorage.
 * Server data never goes here: it belongs to TanStack Query.
 */
export const usePreferences = create<PreferencesState>()(
  persist(
    (set) => ({
      theme: 'system',
      sidebarCollapsed: false,
      setTheme: (theme) => set({ theme }),
      toggleSidebar: () => set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
    }),
    {
      name: 'prospecter-preferences',
      partialize: ({ theme, sidebarCollapsed }) => ({ theme, sidebarCollapsed }),
    },
  ),
)

const darkQuery = '(prefers-color-scheme: dark)'

function subscribeToSystemTheme(onChange: () => void) {
  const media = window.matchMedia(darkQuery)
  media.addEventListener('change', onChange)
  return () => media.removeEventListener('change', onChange)
}

/** The theme currently displayed: `system` resolves to the OS preference. */
export function useResolvedTheme(): 'light' | 'dark' {
  const theme = usePreferences((state) => state.theme)
  const systemIsDark = useSyncExternalStore(
    subscribeToSystemTheme,
    () => window.matchMedia(darkQuery).matches,
  )

  return theme === 'system' ? (systemIsDark ? 'dark' : 'light') : theme
}

/** The browser's bar color in each theme, the page's background. index.html holds the same values. */
const BROWSER_BAR = { light: '#f8fafc', dark: '#0f1117' } as const

function setMetaContent(name: string, content: string) {
  const meta = document.head.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)
  if (meta !== null) {
    meta.content = content
  }
}

/**
 * Keeps the `dark` class on <html>, the native controls (`color-scheme`) and the browser's bar (`theme-color`) in sync
 * with the app's theme, not the system's. index.html sets the initial values.
 */
export function useThemeClass() {
  const resolved = useResolvedTheme()

  useEffect(() => {
    document.documentElement.classList.toggle('dark', resolved === 'dark')
    setMetaContent('color-scheme', resolved)
    setMetaContent('theme-color', BROWSER_BAR[resolved])
  }, [resolved])
}
