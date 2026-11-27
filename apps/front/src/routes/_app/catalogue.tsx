import { createFileRoute } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { EventCatalogue } from '@/features/catalogue/components/event-catalogue'
import { PageHeader } from '@/shared/ui/page-header'

/** The shared event components in every state, read by eye in both themes. No navigation leads to it. */
export const Route = createFileRoute('/_app/catalogue')({
  component: CataloguePage,
})

function CataloguePage() {
  const { t } = useTranslation()

  return (
    <div className="space-y-6">
      <PageHeader title={t('catalogue.title')} description={t('catalogue.description')} />
      <EventCatalogue />
    </div>
  )
}
