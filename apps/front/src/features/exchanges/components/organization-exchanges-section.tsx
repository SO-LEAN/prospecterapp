import { useInfiniteQuery } from '@tanstack/react-query'
import { useNoteOrganizationExchange } from '../mutations'
import { organizationTimelineQuery } from '../queries'
import { ExchangeLine } from './exchange-line'
import { ExchangeForm, type Interlocutors } from './exchange-form'
import { NoteExchangeDialog, TimelineCard } from './timeline-card'

type Props = { organizationId: string; interlocutors: Interlocutors; className?: string }

/**
 * The timeline of the organization: its exchanges and those of its contacts, each with who communicated, the
 * most recent first (ADR 0076).
 */
export function OrganizationExchangesSection({ organizationId, interlocutors, className }: Props) {
  const timeline = useInfiniteQuery(organizationTimelineQuery(organizationId))
  const note = useNoteOrganizationExchange()

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
      keyOf={(item) => item.exchange.id}
      renderItem={(item) => <ExchangeLine exchange={item.exchange} interlocutor={item.interlocutor} />}
      noteExchange={
        mayNote && (
          <NoteExchangeDialog name={interlocutors.organization}>
            {(close) => (
              <ExchangeForm
                interlocutors={interlocutors}
                onCancel={close}
                onSubmit={async (exchange, personId) => {
                  await note.mutateAsync({
                    params: { path: { id: organizationId } },
                    body: { exchange, personId },
                  })
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
