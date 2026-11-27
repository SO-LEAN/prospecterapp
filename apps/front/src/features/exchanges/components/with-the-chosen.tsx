import { useInfiniteQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { usePlanExchange, usePlanOrganizationExchange } from '../mutations'
import type { Slot } from '../planning'
import { organizationTimelineQuery, timelineQuery } from '../queries'
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
    <MayPlan chosen={chosen} timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <ContactPlanForm
        contactId={chosen.id}
        slot={slot}
        onCancel={onCancel}
        onSubmit={async (body) => {
          await plan.mutateAsync({ params: { path: { id: chosen.id } }, body })
          onPlanned()
        }}
      />
    </MayPlan>
  )
}

function PlanWithTheOrganization({ chosen, slot, onCancel, onPlanned }: PlanWithProps) {
  const plan = usePlanOrganizationExchange()
  const timeline = useInfiniteQuery(organizationTimelineQuery(chosen.id))

  return (
    <MayPlan chosen={chosen} timeline={timeline.data?.pages[0]} onCancel={onCancel}>
      <PlanForm
        slot={slot}
        onCancel={onCancel}
        onSubmit={async (exchange) => {
          await plan.mutateAsync({ params: { path: { id: chosen.id } }, body: { personId: null, exchange } })
          onPlanned()
        }}
      />
    </MayPlan>
  )
}

type MayPlanProps = {
  chosen: Chosen
  /** The first slice of its timeline, which offers planning when planning is allowed. */
  timeline: { _links: { planExchange?: unknown } } | undefined
  onCancel: () => void
  children: ReactNode
}

/** The form, once the chosen one's timeline is read and offers planning. Otherwise it says why not and goes back to the search. */
function MayPlan({ chosen, timeline, onCancel, children }: MayPlanProps) {
  const { t } = useTranslation()

  if (!timeline) return null
  if (!timeline._links.planExchange)
    return (
      <div className="space-y-3">
        <Alert>
          <AlertDescription>{t('calendar.mayNotPlan', { name: chosen.name })}</AlertDescription>
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
