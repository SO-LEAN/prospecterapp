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

type Props<Item> = {
  timeline: UseInfiniteQueryResult
  items: Item[]
  keyOf: (item: Item) => string
  renderItem: (item: Item) => ReactNode
  /** The quick entry of a new exchange, when the timeline offers it. */
  noteExchange?: ReactNode
  className?: string
}

/** A timeline, of a contact or of an organization: the most recent first, what follows loaded on scrolling. */
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
            <li key={keyOf(item)} className="py-3 first:pt-0 last:pb-0">
              {renderItem(item)}
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
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('exchanges.note')}</DialogTitle>
          <DialogDescription>{t('exchanges.noteDescription', { name })}</DialogDescription>
        </DialogHeader>
        {children(() => setOpen(false))}
      </DialogContent>
    </Dialog>
  )
}
