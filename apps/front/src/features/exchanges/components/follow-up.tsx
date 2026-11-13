import { CalendarPlus } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { type Channel, type ExchangeFacts, isAgreedUpon, type PlannedExchangeInput } from '../queries'
import { ExchangeForm, type Interlocutors } from './exchange-form'
import { PlanForm } from './plan-form'

type Props = {
  /** The channel of the exchange it follows, proposed again when it is planned. */
  channel: Channel
  plan: (exchange: PlannedExchangeInput) => Promise<unknown>
  onDone: () => void
}

/** Right after an exchange, what follows is planned with the same interlocutor, or left in a click. */
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

/** The exchange noted, with whom: the person, null for the organization itself or on the card of a contact. */
type Noted = { channel: Channel; personId: string | null }

type NoteProps = {
  /** Notes the exchange, then joins its files. */
  note: (facts: ExchangeFacts, personId: string | null, files: File[]) => Promise<unknown>
  /** Plans with the same interlocutor; absent when planning is not offered. */
  plan?: (exchange: PlannedExchangeInput, personId: string | null) => Promise<unknown>
  interlocutors?: Interlocutors
  close: () => void
}

/** An exchange noted, then what follows proposed with the same interlocutor when planning is offered. */
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
