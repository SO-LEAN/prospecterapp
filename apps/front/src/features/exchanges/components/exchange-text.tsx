import { ShieldAlert } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'

type Props = {
  text: string
  /** The reader has no right to manage the personal data it may hold. */
  flagged: boolean
  /** Its contact forgotten, it may still hold their personal data until it is made conform (ADR 0079). */
  toMakeConform: boolean
  className?: string
}

/** What was said; on a yellow ground when it may hold personal data the reader should know of. */
export function ExchangeText({ text, flagged, toMakeConform, className }: Props) {
  const { t } = useTranslation()

  if (!flagged && !toMakeConform) {
    return <p className={className}>{text}</p>
  }

  return (
    <PersonalDataWarning
      warning={toMakeConform ? t('exchanges.toMakeConform') : t('exchanges.mayHoldPersonalData')}
    >
      <p className={cn('text-foreground', className)}>{text}</p>
    </PersonalDataWarning>
  )
}

/** A yellow ground, its warning above what it is about, if anything. */
export function PersonalDataWarning({ warning, children }: { warning: string; children?: ReactNode }) {
  return (
    <div className="mt-1 rounded-md bg-warning/15 px-2 py-1">
      <p className="flex items-center gap-1 font-medium text-xs">
        <ShieldAlert aria-hidden className="size-3.5 shrink-0 text-warning" />
        {warning}
      </p>
      {children}
    </div>
  )
}
