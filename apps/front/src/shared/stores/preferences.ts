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
 * Client-only UI preferences, persisted in localStorage.
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

/** The theme actually displayed: `system` resolved against the OS preference. */
export function useResolvedTheme(): 'light' | 'dark' {
  const theme = usePreferences((state) => state.theme)
  const systemIsDark = useSyncExternalStore(
    subscribeToSystemTheme,
    () => window.matchMedia(darkQuery).matches,
  )

  return theme === 'system' ? (systemIsDark ? 'dark' : 'light') : theme
}

/** Keeps the `dark` class of <html> in sync with the preference (initial value set in index.html). */
export function useThemeClass() {
  const resolved = useResolvedTheme()

  useEffect(() => {
    document.documentElement.classList.toggle('dark', resolved === 'dark')
  }, [resolved])
}
