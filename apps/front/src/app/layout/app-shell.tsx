import { Menu } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatDayAndWeek } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { usePreferences } from '@/shared/stores/preferences'
import { Button } from '@/shared/ui/button'
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/shared/ui/sheet'
import { BrandMark } from './brand'
import { Breadcrumb } from './breadcrumb'
import { Navigation } from './navigation'
import { NewMenu } from './new-menu'

/** « Nouveau » is the gold of the top bar. Its menu owns its button, so the bar dresses it from outside. */
const GOLDEN_BUTTON =
  '[&_[data-slot=button]]:bg-gold [&_[data-slot=button]]:text-gold-foreground [&_[data-slot=button]:hover]:bg-gold/90'

export function AppShell({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  const collapsed = usePreferences((state) => state.sidebarCollapsed)
  const [mobileOpen, setMobileOpen] = useState(false)

  return (
    <div className="flex min-h-dvh">
      <aside
        className={cn(
          'sticky top-0 hidden h-dvh shrink-0 border-sidebar-border border-r bg-sidebar transition-[width] duration-200 md:block',
          collapsed ? 'w-16' : 'w-58',
        )}
      >
        <Navigation collapsed={collapsed} />
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex h-13 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:px-8">
          <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
            <SheetTrigger asChild>
              <Button variant="ghost" size="icon" className="md:hidden" aria-label={t('nav.expand')}>
                <Menu />
              </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-72 border-sidebar-border bg-sidebar p-0">
              <SheetTitle className="sr-only">{t('nav.mainNavigation')}</SheetTitle>
              <Navigation onNavigate={() => setMobileOpen(false)} />
            </SheetContent>
          </Sheet>
          <BrandMark className="size-7 md:hidden" />
          <div className="hidden min-w-0 items-center gap-3 sm:flex">
            <Breadcrumb />
            <span className="shrink-0 rounded-full border bg-card px-2.5 py-0.5 text-muted-foreground text-xs tabular-nums">
              {formatDayAndWeek(new Date())}
            </span>
          </div>
          <div className={cn('ml-auto flex items-center gap-3', GOLDEN_BUTTON)}>
            <NewMenu />
          </div>
        </header>

        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-8">{children}</main>
      </div>
    </div>
  )
}
