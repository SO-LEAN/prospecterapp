import { useTranslation } from 'react-i18next'

/** A campaign with nobody left to handle has ended. This component says so in a sentence. */
export function CampaignEnded() {
  const { t } = useTranslation()

  return (
    <p role="status" className="rounded-md border p-3 font-medium text-sm">
      {t('campaigns.run.ended')}
    </p>
  )
}
