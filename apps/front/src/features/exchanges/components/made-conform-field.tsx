import type { UseFormRegisterReturn } from 'react-hook-form'
import { useTranslation } from 'react-i18next'

/** When ticked, the correction declares the exchange redacted, and its yellow ground goes away (ADR 0079). */
export function MadeConformField(checkbox: UseFormRegisterReturn) {
  const { t } = useTranslation()

  return (
    <label className="flex items-center gap-2 text-sm">
      <input type="checkbox" className="size-4 accent-primary" {...checkbox} />
      {t('exchanges.madeConform')}
    </label>
  )
}
