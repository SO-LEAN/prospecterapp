import { useInfiniteQuery } from '@tanstack/react-query'
import { attachTheFiles, useAttachFiles, useNoteExchange, usePlanExchange } from '../mutations'
import { timelineQuery, upcomingQuery } from '../queries'
import { ExchangeForm } from './exchange-form'
import { ExchangeLine } from './exchange-line'
import { PlanForm } from './plan-form'
import { NoteExchangeDialog, PlanExchangeDialog, TimelineCard } from './timeline-card'

type Props = { contactId: string; contactName: string; className?: string }

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
