import { useInfiniteQuery } from '@tanstack/react-query'
import { CalendarClock } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { formatMoment, formatUpcoming } from '@/shared/lib/format'
import { Badge } from '@/shared/ui/badge'
import { upcomingQuery } from '../queries'

/** The next exchange planned with the contact, the first of those to come; nothing while none is. */
export function NextExchange({ contactId }: { contactId: string }) {
  const { t } = useTranslation()
  const next = useInfiniteQuery(upcomingQuery(contactId)).data?.pages[0]?.items[0]

  if (!next) {
    return null
  }

  return (
    <Badge variant="outline">
      <CalendarClock aria-hidden />
      <time dateTime={next.occurredAt} title={formatMoment(next.occurredAt)}>
        {t('exchanges.nextExchange', {
          channel: t(`exchanges.channels.${next.channel}`),
          moment: formatUpcoming(next.occurredAt),
        })}
      </time>
    </Badge>
  )
}
