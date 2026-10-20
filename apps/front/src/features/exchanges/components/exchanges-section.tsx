import { useInfiniteQuery } from '@tanstack/react-query'
import { useNoteExchange } from '../mutations'
import { timelineQuery } from '../queries'
import { ExchangeLine } from './exchange-line'
import { ExchangeForm } from './exchange-form'
import { NoteExchangeDialog, TimelineCard } from './timeline-card'

type Props = { contactId: string; contactName: string; className?: string }

/** The timeline of the contact, the most recent first, what follows loaded on scrolling. */
export function ExchangesSection({ contactId, contactName, className }: Props) {
  const timeline = useInfiniteQuery(timelineQuery(contactId))
  const note = useNoteExchange()

  if (!timeline.data) {
    return null
  }
  const slices = timeline.data.pages
  const mayNote = Boolean(slices[0]?._links.noteExchange)

  return (
    <TimelineCard
      className={className}
      timeline={timeline}
      items={slices.flatMap((slice) => slice.items)}
      keyOf={(exchange) => exchange.id}
      renderItem={(exchange) => <ExchangeLine exchange={exchange} />}
      noteExchange={
        mayNote && (
          <NoteExchangeDialog name={contactName}>
            {(close) => (
              <ExchangeForm
                onCancel={close}
                onSubmit={async (body) => {
                  await note.mutateAsync({ params: { path: { id: contactId } }, body })
                  close()
                }}
              />
            )}
          </NoteExchangeDialog>
        )
      }
    />
  )
}
