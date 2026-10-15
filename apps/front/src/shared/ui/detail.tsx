import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

/** One labelled value of a section; "not provided" when empty. */
export function Detail({ label, children }: { label: string; children: ReactNode }) {
  const { t } = useTranslation()

  return (
    <div className="grid grid-cols-[8rem_1fr] gap-3 py-1">
      <span className="text-muted-foreground">{label}</span>
      <span className="min-w-0 break-words">
        {children || <span className="text-muted-foreground">{t('common.notProvided')}</span>}
      </span>
    </div>
  )
}
