import { useInfiniteQuery } from '@tanstack/react-query'
import { attachTheFiles, useAttachFiles, useNoteExchange, usePlanExchange } from '../mutations'
import { timelineQuery, upcomingQuery } from '../queries'
import { ExchangeForm } from './exchange-form'
import { ExchangeLine } from './exchange-line'
import { NoteThenFollowUp } from './follow-up'
import { PlanForm } from './plan-form'
import { NoteExchangeDialog, PlanExchangeDialog, ProposedExchangeDialog, TimelineCard } from './timeline-card'

type Props = { contactId: string; contactName: string; className?: string }

type ProposedProps = { contactId: string; contactName: string; open: boolean; onClose: () => void }

/** The exchange with the contact a gesture led to, noted now when the member accepts to. */
export function NoteTheExchangeWithTheContact({ contactId, contactName, open, onClose }: ProposedProps) {
  const note = useNoteExchange()
  const attach = useAttachFiles()

  return (
    <ProposedExchangeDialog name={contactName} open={open} onClose={onClose}>
      <ExchangeForm
        withFiles
        onCancel={onClose}
        onSubmit={async (body, _itself, files) => {
          const { noted } = await note.mutateAsync({ contactId, body })
          onClose()
          attachTheFiles(attach, noted, files)
        }}
      />
    </ProposedExchangeDialog>
  )
}

/** The timeline of the contact: what is to come, the soonest first, then the past, the most recent first, what follows loaded on scrolling. */
export function ExchangesSection({ contactId, contactName, className }: Props) {
  const timeline = useInfiniteQuery(timelineQuery(contactId))
  const upcoming = useInfiniteQuery(upcomingQuery(contactId))
  const note = useNoteExchange()
  const plan = usePlanExchange()
  const attach = useAttachFiles()

  if (!timeline.data) {
    return null
  }
  const slices = timeline.data.pages
  const mayNote = Boolean(slices[0]?._links.noteExchange)
  const mayPlan = Boolean(slices[0]?._links.planExchange)

  return (
    <TimelineCard
      className={className}
      timeline={timeline}
      upcoming={upcoming}
      upcomingItems={upcoming.data?.pages.flatMap((slice) => slice.items)}
      items={slices.flatMap((slice) => slice.items)}
      keyOf={(exchange) => exchange.id}
      renderItem={(exchange) => <ExchangeLine exchange={exchange} />}
      planExchange={
        mayPlan && (
          <PlanExchangeDialog name={contactName}>
            {(close) => (
              <PlanForm
                onCancel={close}
                onSubmit={async (body) => {
                  await plan.mutateAsync({ params: { path: { id: contactId } }, body })
                  close()
                }}
              />
            )}
          </PlanExchangeDialog>
        )
      }
      noteExchange={
        mayNote && (
          <NoteExchangeDialog name={contactName}>
            {(close) => (
              <NoteThenFollowUp
                close={close}
                note={async (body, _itself, files) => {
                  const { noted } = await note.mutateAsync({ contactId, body })
                  attachTheFiles(attach, noted, files)
                }}
                plan={
                  mayPlan
                    ? (body) => plan.mutateAsync({ params: { path: { id: contactId } }, body })
                    : undefined
                }
              />
            )}
          </NoteExchangeDialog>
        )
      }
    />
  )
}
