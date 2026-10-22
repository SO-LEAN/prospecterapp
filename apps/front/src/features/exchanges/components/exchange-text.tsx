import { ShieldAlert } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'

type Props = { text: string; flagged: boolean; className?: string }

/** What was said; flagged when the reader has no right to manage the personal data it may hold. */
export function ExchangeText({ text, flagged, className }: Props) {
  const { t } = useTranslation()

  if (!flagged) {
    return <p className={className}>{text}</p>
  }

  return (
    <div className="mt-1 rounded-md bg-warning/15 px-2 py-1">
      <p className="flex items-center gap-1 font-medium text-xs">
        <ShieldAlert aria-hidden className="size-3.5 shrink-0 text-warning" />
        {t('exchanges.mayHoldPersonalData')}
      </p>
      <p className={cn('text-foreground', className)}>{text}</p>
    </div>
  )
}
