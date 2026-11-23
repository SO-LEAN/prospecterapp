import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { BookingSection } from '@/features/booking/components/booking-section'
import { myBookingPageQuery } from '@/features/booking/queries'
import { checkMyConnections } from '@/features/connectors/check-my-connections'
import { ConnectorsSection } from '@/features/connectors/components/connectors-section'
import { RemindersSection } from '@/features/settings/components/reminders-section'
import { SETTINGS_SECTIONS, SettingsNavigation } from '@/features/settings/components/settings-navigation'
import { remindersQuery } from '@/features/settings/queries'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const searchSchema = z.object({
  section: z.enum(SETTINGS_SECTIONS).catch('reminders').default('reminders'),
})

/**
 * The member's settings, section by section. The URL holds the section shown. The connectors are checked with their
 * providers each time their section opens. A preload (a hovered link) never checks them, because a check calls the
 * provider.
 */
export const Route = createFileRoute('/_app/settings')({
  validateSearch: zodValidator(searchSchema),
  loaderDeps: ({ search }) => ({ section: search.section }),
  loader: async ({ context: { queryClient }, deps: { section }, preload }) => {
    if (section === 'connectors') {
      return { connectionsCheck: preload ? undefined : await checkMyConnections(queryClient) }
    }
    if (section === 'booking') {
      await queryClient.ensureQueryData(myBookingPageQuery())
      return { connectionsCheck: undefined }
    }
    await queryClient.ensureQueryData(remindersQuery())
    return { connectionsCheck: undefined }
  },
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: SettingsPage,
})

function SettingsPage() {
  const { t } = useTranslation()
  const { section } = Route.useSearch()
  const { connectionsCheck } = Route.useLoaderData()

  return (
    <div className="space-y-8">
      <PageHeader title={t('settings.title')} description={t('settings.subtitle')} />
      <div className="grid grid-cols-1 gap-6 md:grid-cols-[12rem_minmax(0,1fr)]">
        <SettingsNavigation current={section} />
        <div className="max-w-2xl">
          {section === 'reminders' && <MyReminders />}
          {section === 'booking' && <MyBookingPage />}
          {section === 'connectors' && (
            <ConnectorsSection googleUnreachable={connectionsCheck === 'googleUnreachable'} />
          )}
        </div>
      </div>
    </div>
  )
}

function MyReminders() {
  const { data: reminders } = useSuspenseQuery(remindersQuery())

  return <RemindersSection reminders={reminders} />
}

function MyBookingPage() {
  const { data: page } = useSuspenseQuery(myBookingPageQuery())

  return <BookingSection page={page} />
}
