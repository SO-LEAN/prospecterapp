import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { placeOf } from '@/shared/lib/address'
import { attachTheFiles, useAttachFiles, useNoteExchange, usePlanExchange } from '../mutations'
import type { Slot } from '../planning'
import { meetingPlaceQuery, type PlannedExchangeInput, timelineQuery, upcomingQuery } from '../queries'
import { CampaignOfTheLine } from './campaign-of-the-exchange'
import { CatchUpGmail } from './catch-up-gmail'
import { ExchangeForm } from './exchange-form'
import { ExchangeLine } from './exchange-line'
import { NoteThenFollowUp } from './follow-up'
import { JoinTheVideoCall } from './join-the-video-call'
import { PlanForm } from './plan-form'
import { NoteExchangeDialog, PlanExchangeDialog, ProposedExchangeDialog, TimelineCard } from './timeline-card'

type Props = {
  contactId: string
  contactName: string
  /** The contact gives the link to catch up the e-mails exchanged with them. */
  mayCatchUpGmail?: boolean
  className?: string
}

type ProposedProps = { contactId: string; contactName: string; open: boolean; onClose: () => void }

/** The exchange with the contact that a gesture led to. It is logged now when the member accepts. */
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

/** The contact's timeline: the upcoming exchanges, the soonest first, then the past ones, the most recent first. Scrolling loads more. */
export function ExchangesSection({ contactId, contactName, mayCatchUpGmail = false, className }: Props) {
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
      renderItem={(exchange) => <ExchangeLine exchange={exchange} campaignAside />}
      actionOf={(exchange) => <JoinTheVideoCall exchange={exchange} />}
      asideOf={(exchange) => <CampaignOfTheLine exchange={exchange} />}
      catchUp={mayCatchUpGmail && <CatchUpGmail contactId={contactId} />}
      planExchange={
        mayPlan && (
          <PlanExchangeDialog name={contactName}>
            {(close) => (
              <ContactPlanForm
                contactId={contactId}
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
            {(close, planTheFollowUp) => (
              <NoteThenFollowUp
                onPlanning={planTheFollowUp}
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

type ContactPlanFormProps = {
  contactId: string
  onSubmit: (exchange: PlannedExchangeInput) => Promise<unknown>
  onCancel: () => void
  slot?: Slot
}

/** Plans once the place to meet the contact is known. That place is their organization's address, read when planning. */
export function ContactPlanForm({ contactId, onSubmit, onCancel, slot }: ContactPlanFormProps) {
  const meetingPlace = useQuery(meetingPlaceQuery(contactId))

  if (meetingPlace.isPending) {
    return null
  }

  return (
    <PlanForm
      proposedPlace={placeOf(meetingPlace.data?.address)}
      slot={slot}
      onSubmit={onSubmit}
      onCancel={onCancel}
    />
  )
}
