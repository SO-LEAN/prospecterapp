import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { RemindersSection } from '@/features/settings/components/reminders-section'
import { SETTINGS_SECTIONS, SettingsNavigation } from '@/features/settings/components/settings-navigation'
import { remindersQuery } from '@/features/settings/queries'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const searchSchema = z.object({
  section: z.enum(SETTINGS_SECTIONS).catch('reminders').default('reminders'),
})

/** The configuration of the member, section by section, the section shown in the URL. */
export const Route = createFileRoute('/_app/settings')({
  validateSearch: zodValidator(searchSchema),
  loader: ({ context }) => context.queryClient.ensureQueryData(remindersQuery()),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: SettingsPage,
})

function SettingsPage() {
  const { t } = useTranslation()
  const { section } = Route.useSearch()
  const { data: reminders } = useSuspenseQuery(remindersQuery())

  return (
    <div className="space-y-8">
      <PageHeader title={t('settings.title')} description={t('settings.subtitle')} />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-[12rem_minmax(0,1fr)]">
        <SettingsNavigation current={section} />
        <div className="max-w-2xl">
          {section === 'reminders' && <RemindersSection reminders={reminders} />}
        </div>
      </div>
    </div>
  )
}
