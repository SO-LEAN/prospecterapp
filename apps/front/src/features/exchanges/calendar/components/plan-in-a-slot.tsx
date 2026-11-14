import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { Building2, Search, User } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { formatMoment } from '@/shared/lib/format'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { Input } from '@/shared/ui/input'
import { ContactPlanForm } from '../../components/exchanges-section'
import { PlanForm } from '../../components/plan-form'
import { usePlanExchange, usePlanOrganizationExchange } from '../../mutations'
import type { Slot } from '../../planning'
import { organizationTimelineQuery, timelineQuery } from '../../queries'
import { contactsFoundQuery, organizationsFoundQuery } from '../queries'

/** Who the exchange planned in the slot is with: a contact, or the organization itself. */
type Chosen = { kind: 'person' | 'organization'; id: string; name: string }

type Props = { slot: Slot | null; onClose: () => void }

/**
 * A free slot of the calendar, clicked or dragged: the contact or the organization searched as everywhere (the
 * start of the name or of the email), then planned in it, its moment and its duration proposed.
 */
export function PlanInASlot({ slot, onClose }: Props) {
  const { t } = useTranslation()
  const [chosen, setChosen] = useState<Chosen | null>(null)
  const close = () => {
    setChosen(null)
    onClose()
  }

  return (
    <Dialog open={slot !== null} onOpenChange={(open) => !open && close()}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{t('exchanges.plan')}</DialogTitle>
          <DialogDescription>
            {slot && t('calendar.planIn', { moment: formatMoment(slot.startsAt.toISOString()) })}
          </DialogDescription>
        </DialogHeader>
        {slot &&
          (chosen ? (
            <PlanWith chosen={chosen} slot={slot} onCancel={() => setChosen(null)} onPlanned={close} />
          ) : (
            <SearchTheInterlocutor onChoose={setChosen} />
          ))}
      </DialogContent>
    </Dialog>
  )
}

function SearchTheInterlocutor({ onChoose }: { onChoose: (chosen: Chosen) => void }) {
  const { t } = useTranslation()
  const [text, setText] = useState('')
  const q = useDebouncedValue(text.trim())
  const contacts = useQuery({ ...contactsFoundQuery(q), enabled: q !== '' })
  const organizations = useQuery({ ...organizationsFoundQuery(q), enabled: q !== '' })
  const found: Chosen[] = [
    ...(contacts.data?.items ?? []).map((contact) => ({
      kind: 'person' as const,
      id: contact.id,
      name:
        [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ') || t('contacts.unnamed'),
    })),
    ...(organizations.data?.items ?? []).map((organization) => ({
      kind: 'organization' as const,
      id: organization.id,
      name: organization.name,
    })),
  ]

  return (
    <div className="space-y-3">
      <div className="relative">
        <Search aria-hidden className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
        <Input
          type="search"
          autoFocus
          className="pl-8"
          aria-label={t('calendar.searchInterlocutor')}
          placeholder={t('calendar.searchInterlocutor')}
          value={text}
          onChange={(event) => setText(event.target.value)}
        />
      </div>
      {q !== '' && contacts.isSuccess && organizations.isSuccess && found.length === 0 && (
        <p className="text-muted-foreground text-sm">{t('calendar.nobodyFound')}</p>
      )}
      <ul className="space-y-1" aria-label={t('calendar.found')}>
        {found.map((one) => {
          const Icon = one.kind === 'person' ? User : Building2
          return (
            <li key={`${one.kind}-${one.id}`}>
              <Button variant="ghost" className="w-full justify-start" onClick={() => onChoose(one)}>
                <Icon aria-hidden /> {one.name}
              </Button>
            </li>
          )
        })}
      </ul>
    </div>
  )
}

type PlanWithProps = { chosen: Chosen; slot: Slot; onCancel: () => void; onPlanned: () => void }

/** Planned with the contact or the organization chosen, when its timeline offers it. */
function PlanWith(props: PlanWithProps) {
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
  /** The first slice of its timeline, which offers planning when it may be. */
  timeline: { _links: { planExchange?: unknown } } | undefined
  onCancel: () => void
  children: ReactNode
}

/** The form once the timeline of the one chosen is read, if it offers planning; else why not, and back to the search. */
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
