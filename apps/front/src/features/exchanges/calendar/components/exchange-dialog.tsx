import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { SquareArrowOutUpRight } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { ExchangePanel, type StartWith } from '../../components/exchange-detail'
import { ExchangeLine } from '../../components/exchange-line'
import { exchangeQuery, timelineQuery } from '../../queries'
import type { CalendarExchange } from '../queries'

/** How many of the last exchanges with the contact show beside the opened one. */
const LAST_EXCHANGES_SHOWN = 3

type Props = {
  item: CalendarExchange | null
  onClose: () => void
  /** Who the contact is, a short version of their card. The page that composes the calendar with the contacts gives it. */
  contactSummary: (contactId: string) => ReactNode
  /** Opens the exchange on its report, to mark it done at once, or on its move. */
  startWith?: StartWith
}

/**
 * A past or upcoming exchange, opened from the calendar or the day. It shows the whole exchange and what it offers,
 * as its detail does. Then it shows who the exchange is with, their last exchanges and their card. Échap closes it,
 * and the keyboard reaches its controls.
 */
export function ExchangeDialog({ item, onClose, contactSummary, startWith }: Props) {
  const { t } = useTranslation()

  return (
    <Dialog open={item !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] gap-3 overflow-y-auto px-2 sm:max-w-lg">
        <DialogHeader className="px-4">
          <DialogTitle>{t('exchanges.detail.title')}</DialogTitle>
          <DialogDescription className="sr-only">{t('exchanges.detail.description')}</DialogDescription>
        </DialogHeader>
        {item && (
          <>
            <ExchangePanel exchangeId={item.exchange.id} onErased={onClose} startWith={startWith} />
            {item.interlocutor.kind === 'person' && item.interlocutor.presence !== 'forgotten' && (
              <section aria-label={t('calendar.theContact')} className="space-y-3 border-t px-4 pt-3">
                {contactSummary(item.interlocutor.id)}
                <LastExchanges contactId={item.interlocutor.id} />
              </section>
            )}
            <OpenTheCard item={item} />
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}

function LastExchanges({ contactId }: { contactId: string }) {
  const { t } = useTranslation()
  const timeline = useInfiniteQuery(timelineQuery(contactId))
  const last = timeline.data?.pages[0]?.items.slice(0, LAST_EXCHANGES_SHOWN) ?? []

  if (last.length === 0) return null

  return (
    <div className="space-y-2">
      <h3 className="font-medium text-sm">{t('calendar.lastExchanges')}</h3>
      <ul className="space-y-2 text-sm">
        {last.map((exchange) => (
          <li key={exchange.id}>
            <ExchangeLine exchange={exchange} />
          </li>
        ))}
      </ul>
    </div>
  )
}

/** The contact's or the organization's card, when the exchange leads to one. A forgotten contact has none. */
function OpenTheCard({ item }: { item: CalendarExchange }) {
  const { t } = useTranslation()
  const detail = useQuery(exchangeQuery(item.exchange.id))
  const { interlocutor } = item

  if (!detail.data?._links.interlocutor) return null

  return (
    <div className="flex justify-end px-4 pb-2">
      <Button variant="outline" asChild>
        {interlocutor.kind === 'person' ? (
          <Link to="/contacts/$contactId" params={{ contactId: interlocutor.id }}>
            <SquareArrowOutUpRight aria-hidden /> {t('calendar.openTheCard')}
          </Link>
        ) : (
          <Link to="/organizations/$organizationId" params={{ organizationId: interlocutor.id }}>
            <SquareArrowOutUpRight aria-hidden /> {t('calendar.openTheCard')}
          </Link>
        )}
      </Button>
    </div>
  )
}
