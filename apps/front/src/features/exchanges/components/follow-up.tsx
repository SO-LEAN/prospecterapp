import { CalendarPlus } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { type Channel, type ExchangeFacts, isAgreedUpon, type PlannedExchangeInput } from '../queries'
import { ExchangeForm, type Interlocutors } from './exchange-form'
import { PlanForm } from './plan-form'

type Props = {
  /** The previous exchange's channel, proposed again for the follow-up. */
  channel: Channel
  plan: (exchange: PlannedExchangeInput) => Promise<unknown>
  onDone: () => void
}

/** Right after an exchange, plans what follows with the same interlocutor. One click skips it. */
export function FollowUp({ channel, plan, onDone }: Props) {
  const { t } = useTranslation()
  const [planning, setPlanning] = useState(false)

  if (planning) {
    return (
      <PlanForm
        channel={isAgreedUpon(channel) ? channel : undefined}
        onCancel={onDone}
        onSubmit={async (exchange) => {
          await plan(exchange)
          onDone()
        }}
      />
    )
  }

  return (
    <div className="space-y-3">
      <p className="text-sm">{t('exchanges.followUp.question')}</p>
      <div className="flex flex-wrap justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onDone}>
          {t('exchanges.followUp.later')}
        </Button>
        <Button type="button" onClick={() => setPlanning(true)}>
          <CalendarPlus aria-hidden /> {t('exchanges.followUp.plan')}
        </Button>
      </div>
    </div>
  )
}

/** The logged exchange and who it was with: the person, null for the organization itself or on a contact's card. */
type Noted = { channel: Channel; personId: string | null }

type NoteProps = {
  /** Logs the exchange, then attaches its files. */
  note: (facts: ExchangeFacts, personId: string | null, files: File[]) => Promise<unknown>
  /** Plans with the same interlocutor. It is absent when planning is not offered. */
  plan?: (exchange: PlannedExchangeInput, personId: string | null) => Promise<unknown>
  interlocutors?: Interlocutors
  close: () => void
}

/** Logs an exchange. When planning is offered, it then proposes what follows with the same interlocutor. */
export function NoteThenFollowUp({ note, plan, interlocutors, close }: NoteProps) {
  const [noted, setNoted] = useState<Noted | null>(null)

  if (noted && plan) {
    return (
      <FollowUp channel={noted.channel} onDone={close} plan={(exchange) => plan(exchange, noted.personId)} />
    )
  }

  return (
    <ExchangeForm
      withFiles
      interlocutors={interlocutors}
      onCancel={close}
      onSubmit={async (facts, personId, files) => {
        await note(facts, personId, files)
        if (plan) {
          setNoted({ channel: facts.channel, personId })
        } else {
          close()
        }
      }}
    />
  )
}
