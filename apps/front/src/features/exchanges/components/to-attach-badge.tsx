import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import type { Exchange } from '../queries'

/**
 * « À rattacher » beside an exchange whose contact waits to be attached, as the API says, in the timeline, the calendar
 * and the day (ADR 0146).
 */
export function ToAttachBadge({ exchange }: { exchange: Partial<Pick<Exchange, 'contactToAttach'>> }) {
  const { t } = useTranslation()

  if (!exchange.contactToAttach) return null
  return (
    <Badge variant="outline" className="shrink-0 border-warning px-1 py-0 font-normal text-[0.65rem]">
      {t('toAttach.badge')}
    </Badge>
  )
}
