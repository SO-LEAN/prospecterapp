import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import type { Exchange } from '../queries'

/**
 * An exchange kept for no organization is one with a contact waiting to be attached, held by its holder until then:
 * an exchange with an organization is always kept for it (ADR 0146).
 */
export const isWithAContactToAttach = (exchange: Pick<Exchange, 'organizationIds'>) =>
  exchange.organizationIds.length === 0

/** « À rattacher » beside an exchange with a contact to attach, in the timeline, the calendar and the day. */
export function ToAttachBadge({ exchange }: { exchange: Pick<Exchange, 'organizationIds'> }) {
  const { t } = useTranslation()

  if (!isWithAContactToAttach(exchange)) return null
  return (
    <Badge variant="outline" className="shrink-0 border-warning px-1 py-0 font-normal text-[0.65rem]">
      {t('toAttach.badge')}
    </Badge>
  )
}
