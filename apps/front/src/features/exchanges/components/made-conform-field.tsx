import type { UseFormRegisterReturn } from 'react-hook-form'
import { useTranslation } from 'react-i18next'

/** Ticked, the correction declares the exchange made conform: its yellow ground goes (ADR 0079). */
export function MadeConformField(checkbox: UseFormRegisterReturn) {
  const { t } = useTranslation()

  return (
    <label className="flex items-center gap-2 text-sm">
      <input type="checkbox" className="size-4 accent-primary" {...checkbox} />
      {t('exchanges.madeConform')}
    </label>
  )
}
