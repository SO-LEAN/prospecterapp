import { type UseInfiniteQueryResult, useInfiniteQuery } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useState } from 'react'
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
import { useNoteExchange } from '../mutations'
import { type Timeline, timelineQuery } from '../queries'
import { ExchangeLine } from './exchange-line'
import { NoteExchangeForm } from './note-exchange-form'

type Props = { contactId: string; contactName: string; className?: string }

/**
 * The timeline of the contact, the most recent first, what follows loaded on scrolling. A member the API
 * refuses it to (no right to manage personal data) does not see the section.
 */
export function ExchangesSection(props: Props) {
  const timeline = useInfiniteQuery(timelineQuery(props.contactId))

  return timeline.data ? <TimelineCard {...props} timeline={timeline} slices={timeline.data.pages} /> : null
}

function TimelineCard({
  contactId,
  contactName,
  className,
  timeline,
  slices,
}: Props & { timeline: UseInfiniteQueryResult; slices: Timeline[] }) {
  const { t } = useTranslation()
  const { hasNextPage, isFetchingNextPage, fetchNextPage } = timeline
  const loadWhatFollows = () => {
    if (hasNextPage && !isFetchingNextPage) {
      void fetchNextPage()
    }
  }
  const end = useWhenVisible<HTMLDivElement>(loadWhatFollows)
  const exchanges = slices.flatMap((slice) => slice.items)
  const mayNote = Boolean(slices[0]?._links.noteExchange)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{t('exchanges.title')}</CardTitle>
        {mayNote && (
          <CardAction>
            <NoteExchange contactId={contactId} contactName={contactName} />
          </CardAction>
        )}
      </CardHeader>
      <CardContent className="text-sm">
        {exchanges.length === 0 && <p className="text-muted-foreground">{t('exchanges.none')}</p>}
        <ol className="divide-y" aria-label={t('exchanges.title')}>
          {exchanges.map((exchange) => (
            <li key={exchange.id} className="py-3 first:pt-0 last:pb-0">
              <ExchangeLine exchange={exchange} />
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

function NoteExchange({ contactId, contactName }: { contactId: string; contactName: string }) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const note = useNoteExchange()

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
          <DialogDescription>{t('exchanges.noteDescription', { name: contactName })}</DialogDescription>
        </DialogHeader>
        <NoteExchangeForm
          onCancel={() => setOpen(false)}
          onSubmit={async (body) => {
            await note.mutateAsync({ params: { path: { id: contactId } }, body })
            setOpen(false)
          }}
        />
      </DialogContent>
    </Dialog>
  )
}
