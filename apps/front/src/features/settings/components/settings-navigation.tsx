import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'

/** The sections of the configuration, in their order; the connectors come next. */
export const SETTINGS_SECTIONS = ['reminders'] as const
export type SettingsSection = (typeof SETTINGS_SECTIONS)[number]

type Props = { current: SettingsSection }

/** A side navigation on a computer, tabs above the section on a phone. */
export function SettingsNavigation({ current }: Props) {
  const { t } = useTranslation()

  return (
    <nav
      aria-label={t('settings.sectionsNavigation')}
      className="flex gap-1 overflow-x-auto border-b md:flex-col md:border-b-0"
    >
      {SETTINGS_SECTIONS.map((section) => (
        <Link
          key={section}
          to="/settings"
          search={{ section }}
          aria-current={section === current ? 'page' : undefined}
          className={cn(
            'whitespace-nowrap border-transparent border-b-2 px-3 py-2 font-medium text-muted-foreground text-sm transition-colors hover:text-foreground md:rounded-md md:border-b-0',
            section === current && 'border-primary text-foreground md:bg-accent',
          )}
        >
          {t(`settings.sections.${section}`)}
        </Link>
      ))}
    </nav>
  )
}
