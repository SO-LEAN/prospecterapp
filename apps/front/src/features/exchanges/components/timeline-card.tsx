import type { UseInfiniteQueryResult } from '@tanstack/react-query'
import { CalendarPlus, type LucideIcon, Plus } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useWhenVisible } from '@/shared/hooks/use-when-visible'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { ExchangeDetailSheet } from './exchange-detail'

type Props<Item> = {
  timeline: UseInfiniteQueryResult
  /** The exchanges to come, the soonest first, above the past ones, a slice after the other. */
  upcoming?: UseInfiniteQueryResult
  upcomingItems?: Item[]
  items: Item[]
  /** The id of the exchange of an item, the one its detail opens. */
  keyOf: (item: Item) => string
  renderItem: (item: Item) => ReactNode
  /** The quick entry of a new exchange, when the timeline offers it. */
  noteExchange?: ReactNode
  /** The planning of an exchange to come, when the timeline offers it. */
  planExchange?: ReactNode
  /** What narrows the timeline, beside its title. */
  filter?: ReactNode
  className?: string
}

/**
 * A timeline, of a contact or of an organization: what is to come first, the soonest first, then the past, the most
 * recent first, what follows loaded on scrolling; an exchange opens its detail beside the card.
 */
export function TimelineCard<Item>({
  timeline,
  upcoming,
  upcomingItems = [],
  items,
  keyOf,
  renderItem,
  noteExchange,
  planExchange,
  filter,
  className,
}: Props<Item>) {
  const { t } = useTranslation()
  const { hasNextPage, isFetchingNextPage, fetchNextPage } = timeline
  const loadWhatFollows = () => {
    if (hasNextPage && !isFetchingNextPage) {
      void fetchNextPage()
    }
  }
  const end = useWhenVisible<HTMLDivElement>(loadWhatFollows)
  const [opened, setOpened] = useState<string | null>(null)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{t('exchanges.title')}</CardTitle>
        {(filter || noteExchange || planExchange) && (
          <CardAction className="flex flex-wrap justify-end gap-2">
            {filter}
            {planExchange}
            {noteExchange}
          </CardAction>
        )}
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {upcomingItems.length > 0 && (
          <section className="space-y-1">
            <h3 className="font-medium text-muted-foreground text-xs uppercase">{t('exchanges.upcoming')}</h3>
            <Exchanges
              label={t('exchanges.upcoming')}
              items={upcomingItems}
              keyOf={keyOf}
              renderItem={renderItem}
              onOpen={setOpened}
            />
            {upcoming?.hasNextPage && (
              <div className="flex justify-center pt-1">
                <Button
                  variant="outline"
                  onClick={() => void upcoming.fetchNextPage()}
                  disabled={upcoming.isFetchingNextPage}
                >
                  {t('common.loadMore')}
                </Button>
              </div>
            )}
          </section>
        )}
        <section className="space-y-1">
          {upcomingItems.length > 0 && (
            <h3 className="font-medium text-muted-foreground text-xs uppercase">{t('exchanges.past')}</h3>
          )}
          {items.length === 0 && <p className="text-muted-foreground">{t('exchanges.none')}</p>}
          <Exchanges
            label={t('exchanges.title')}
            items={items}
            keyOf={keyOf}
            renderItem={renderItem}
            onOpen={setOpened}
          />
        </section>
        <div ref={end} />
        {hasNextPage && (
          <div className="flex justify-center pt-3">
            <Button variant="outline" onClick={loadWhatFollows} disabled={isFetchingNextPage}>
              {t('common.loadMore')}
            </Button>
          </div>
        )}
        <ExchangeDetailSheet exchangeId={opened} onClose={() => setOpened(null)} />
      </CardContent>
    </Card>
  )
}

type ExchangesProps<Item> = Pick<Props<Item>, 'items' | 'keyOf' | 'renderItem'> & {
  label: string
  onOpen: (exchangeId: string) => void
}

function Exchanges<Item>({ label, items, keyOf, renderItem, onOpen }: ExchangesProps<Item>) {
  return (
    <ol className="divide-y" aria-label={label}>
      {items.map((item) => (
        <li key={keyOf(item)} className="py-1 first:pt-0 last:pb-0">
          <button
            type="button"
            className="-mx-2 w-[calc(100%+1rem)] rounded-md px-2 py-2 text-left transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            onClick={() => onOpen(keyOf(item))}
          >
            {renderItem(item)}
          </button>
        </li>
      ))}
    </ol>
  )
}

type ExchangeDialogProps = {
  name: string
  children: (close: () => void) => ReactNode
}

/** The button opening the quick entry of an exchange with this name, in a dialog it closes once noted. */
export function NoteExchangeDialog({ name, children }: ExchangeDialogProps) {
  const { t } = useTranslation()

  return (
    <ExchangeDialog
      icon={Plus}
      title={t('exchanges.note')}
      description={t('exchanges.noteDescription', { name })}
    >
      {children}
    </ExchangeDialog>
  )
}

type ProposedProps = { name: string; open: boolean; onClose: () => void; children: ReactNode }

/** The quick entry of an exchange with this name, opened by the page rather than by a button: proposed after a gesture. */
export function ProposedExchangeDialog({ name, open, onClose, children }: ProposedProps) {
  const { t } = useTranslation()

  return (
    <Dialog open={open} onOpenChange={(opened) => !opened && onClose()}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{t('exchanges.note')}</DialogTitle>
          <DialogDescription>{t('exchanges.noteDescription', { name })}</DialogDescription>
        </DialogHeader>
        {children}
      </DialogContent>
    </Dialog>
  )
}

/** The button opening the planning of an exchange with this name, in a dialog it closes once planned. */
export function PlanExchangeDialog({ name, children }: ExchangeDialogProps) {
  const { t } = useTranslation()

  return (
    <ExchangeDialog
      icon={CalendarPlus}
      variant="outline"
      title={t('exchanges.plan')}
      description={t('exchanges.planDescription', { name })}
    >
      {children}
    </ExchangeDialog>
  )
}

type DialogProps = {
  icon: LucideIcon
  variant?: 'default' | 'outline'
  title: string
  description: string
  children: (close: () => void) => ReactNode
}

function ExchangeDialog({ icon: Icon, variant = 'default', title, description, children }: DialogProps) {
  const [open, setOpen] = useState(false)

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button size="sm" variant={variant}>
          <Icon aria-hidden /> {title}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        {children(() => setOpen(false))}
      </DialogContent>
    </Dialog>
  )
}
