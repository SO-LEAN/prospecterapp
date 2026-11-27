import { useInfiniteQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import {
  attachTheFiles,
  useAttachFiles,
  useNoteExchange,
  useNoteOrganizationExchange,
  usePlanExchange,
  usePlanOrganizationExchange,
} from '../mutations'
import type { Slot } from '../planning'
import { organizationTimelineQuery, timelineQuery } from '../queries'
import { ExchangeForm } from './exchange-form'
import { ContactPlanForm } from './exchanges-section'
import { PlanForm } from './plan-form'

/** Who the exchange is with: a contact or the organization itself. */
export type Chosen = { kind: 'person' | 'organization'; id: string; name: string }

/** The search for a contact or an organization. The page that composes the exchanges with them gives it. */
export type InterlocutorSearch = (onChoose: (chosen: Chosen) => void) => ReactNode

type PlanWithProps = {
  chosen: Chosen
  /** The free slot it is planned in, whose moment and duration the form proposes. */
  slot?: Slot
  onCancel: () => void
  onPlanned: () => void
}

/** Plans with the chosen contact or organization, when its timeline offers it. */
export function PlanWith(props: PlanWithProps) {
  return props.chosen.kind === 'person' ? (
    <PlanWithTheContact {...props} />
  ) : (
    <PlanWithTheOrganization {...props} />
  )
}

function PlanWithTheContact({ chosen, slot, onCancel, onPlanned }: PlanWithProps) {
  const plan = usePlanExchange()
  const timeline = useInfiniteQuery(timelineQuery(chosen.id))

  return (
    <May chosen={chosen} gesture="planExchange" timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <ContactPlanForm
        contactId={chosen.id}
        slot={slot}
        onCancel={onCancel}
        onSubmit={async (body) => {
          await plan.mutateAsync({ params: { path: { id: chosen.id } }, body })
          onPlanned()
        }}
      />
    </May>
  )
}

function PlanWithTheOrganization({ chosen, slot, onCancel, onPlanned }: PlanWithProps) {
  const plan = usePlanOrganizationExchange()
  const timeline = useInfiniteQuery(organizationTimelineQuery(chosen.id))

  return (
    <May chosen={chosen} gesture="planExchange" timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <PlanForm
        slot={slot}
        onCancel={onCancel}
        onSubmit={async (exchange) => {
          await plan.mutateAsync({ params: { path: { id: chosen.id } }, body: { personId: null, exchange } })
          onPlanned()
        }}
      />
    </May>
  )
}

/** The link of a timeline that offers the gesture, and the refusal said when it does not. */
const REFUSALS = { planExchange: 'calendar.mayNotPlan', noteExchange: 'exchanges.mayNotNote' } as const

type MayProps = {
  chosen: Chosen
  gesture: keyof typeof REFUSALS
  /** The first slice of its timeline, which carries the gesture's link when the gesture is allowed. */
  timeline: { _links: Partial<Record<keyof typeof REFUSALS, unknown>> } | undefined
  onCancel: () => void
  children: ReactNode
}

/** The form, once the chosen one's timeline is read and offers the gesture. Otherwise it says why not and goes back to the search. */
function May({ chosen, gesture, timeline, onCancel, children }: MayProps) {
  const { t } = useTranslation()

  if (!timeline) return null
  if (!timeline._links[gesture])
    return (
      <div className="space-y-3">
        <Alert>
          <AlertDescription>{t(REFUSALS[gesture], { name: chosen.name })}</AlertDescription>
        </Alert>
        <Button variant="ghost" onClick={onCancel}>
          {t('common.back')}
        </Button>
      </div>
    )

  return (
    <div className="space-y-3">
      <p className="font-medium text-sm">{t('calendar.planWith', { name: chosen.name })}</p>
      {children}
    </div>
  )
}

type NoteWithProps = { chosen: Chosen; onCancel: () => void; onNoted: () => void }

/** Logs an exchange with the chosen contact or organization. */
export function NoteWith(props: NoteWithProps) {
  return props.chosen.kind === 'person' ? (
    <NoteWithTheContact {...props} />
  ) : (
    <NoteWithTheOrganization {...props} />
  )
}

function NoteWithTheContact({ chosen, onCancel, onNoted }: NoteWithProps) {
  const note = useNoteExchange()
  const attach = useAttachFiles()
  const timeline = useInfiniteQuery(timelineQuery(chosen.id))

  return (
    <May chosen={chosen} gesture="noteExchange" timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <ExchangeForm
        withFiles
        onCancel={onCancel}
        onSubmit={async (body, _itself, files) => {
          const { noted } = await note.mutateAsync({ contactId: chosen.id, body })
          onNoted()
          attachTheFiles(attach, noted, files)
        }}
      />
    </May>
  )
}

function NoteWithTheOrganization({ chosen, onCancel, onNoted }: NoteWithProps) {
  const note = useNoteOrganizationExchange()
  const attach = useAttachFiles()
  const timeline = useInfiniteQuery(organizationTimelineQuery(chosen.id))

  return (
    <May chosen={chosen} gesture="noteExchange" timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <ExchangeForm
        withFiles
        onCancel={onCancel}
        onSubmit={async (exchange, _itself, files) => {
          const { noted } = await note.mutateAsync({
            organizationId: chosen.id,
            body: { exchange, personId: null },
          })
          onNoted()
          attachTheFiles(attach, noted, files)
        }}
      />
    </May>
  )
}
