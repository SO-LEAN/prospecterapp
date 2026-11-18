import { useTranslation } from 'react-i18next'

/** A campaign nobody is left to handle in: it is ended, said in a sentence. */
export function CampaignEnded() {
  const { t } = useTranslation()

  return (
    <p role="status" className="rounded-md border p-3 font-medium text-sm">
      {t('campaigns.run.ended')}
    </p>
  )
}
