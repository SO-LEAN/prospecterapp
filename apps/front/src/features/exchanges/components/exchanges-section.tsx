import { useInfiniteQuery } from '@tanstack/react-query'
import { attachTheFiles, useAttachFiles, useNoteExchange } from '../mutations'
import { timelineQuery } from '../queries'
import { ExchangeForm } from './exchange-form'
import { ExchangeLine } from './exchange-line'
import { NoteExchangeDialog, TimelineCard } from './timeline-card'

type Props = { contactId: string; contactName: string; className?: string }

/** The timeline of the contact, the most recent first, what follows loaded on scrolling. */
export function ExchangesSection({ contactId, contactName, className }: Props) {
  const timeline = useInfiniteQuery(timelineQuery(contactId))
  const note = useNoteExchange()
  const attach = useAttachFiles()

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
                withFiles
                onCancel={close}
                onSubmit={async (body, _itself, files) => {
                  const { noted } = await note.mutateAsync({ contactId, body })
                  close()
                  attachTheFiles(attach, noted, files)
                }}
              />
            )}
          </NoteExchangeDialog>
        )
      }
    />
  )
}
