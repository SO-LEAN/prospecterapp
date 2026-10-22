import type { UseInfiniteQueryResult } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
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
  items: Item[]
  /** The id of the exchange of an item, the one its detail opens. */
  keyOf: (item: Item) => string
  renderItem: (item: Item) => ReactNode
  /** The quick entry of a new exchange, when the timeline offers it. */
  noteExchange?: ReactNode
  className?: string
}

/**
 * A timeline, of a contact or of an organization: the most recent first, what follows loaded on scrolling; an
 * exchange opens its detail beside the card.
 */
export function TimelineCard<Item>({
  timeline,
  items,
  keyOf,
  renderItem,
  noteExchange,
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
        {noteExchange && <CardAction>{noteExchange}</CardAction>}
      </CardHeader>
      <CardContent className="text-sm">
        {items.length === 0 && <p className="text-muted-foreground">{t('exchanges.none')}</p>}
        <ol className="divide-y" aria-label={t('exchanges.title')}>
          {items.map((item) => (
            <li key={keyOf(item)} className="py-1 first:pt-0 last:pb-0">
              <button
                type="button"
                className="-mx-2 w-[calc(100%+1rem)] rounded-md px-2 py-2 text-left transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                onClick={() => setOpened(keyOf(item))}
              >
                {renderItem(item)}
              </button>
            </li>
          ))}
        </ol>
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

type NoteExchangeDialogProps = {
  name: string
  children: (close: () => void) => ReactNode
}

/** The button opening the quick entry of an exchange with this name, in a dialog it closes once noted. */
export function NoteExchangeDialog({ name, children }: NoteExchangeDialogProps) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button size="sm">
          <Plus aria-hidden /> {t('exchanges.note')}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{t('exchanges.note')}</DialogTitle>
          <DialogDescription>{t('exchanges.noteDescription', { name })}</DialogDescription>
        </DialogHeader>
        {children(() => setOpen(false))}
      </DialogContent>
    </Dialog>
  )
}
