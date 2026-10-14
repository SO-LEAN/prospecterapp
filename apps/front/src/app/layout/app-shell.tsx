import { Menu } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { usePreferences } from '@/shared/stores/preferences'
import { Button } from '@/shared/ui/button'
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/shared/ui/sheet'
import { BrandMark } from './brand'
import { Navigation } from './navigation'
import { UserMenu } from './user-menu'

export function AppShell({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  const collapsed = usePreferences((state) => state.sidebarCollapsed)
  const [mobileOpen, setMobileOpen] = useState(false)

  return (
    <div className="flex min-h-dvh">
      <aside
        className={cn(
          'sticky top-0 hidden h-dvh shrink-0 border-sidebar-border border-r bg-sidebar transition-[width] duration-200 md:block',
          collapsed ? 'w-[4.25rem]' : 'w-60',
        )}
      >
        <Navigation collapsed={collapsed} />
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:px-8">
          <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
            <SheetTrigger asChild>
              <Button variant="ghost" size="icon" className="md:hidden" aria-label={t('nav.expand')}>
                <Menu />
              </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-72 bg-sidebar p-0">
              <SheetTitle className="sr-only">{t('nav.mainNavigation')}</SheetTitle>
              <Navigation onNavigate={() => setMobileOpen(false)} />
            </SheetContent>
          </Sheet>
          <BrandMark className="size-7 md:hidden" />
          <div className="ml-auto">
            <UserMenu />
          </div>
        </header>

        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-8">{children}</main>
      </div>
    </div>
  )
}
