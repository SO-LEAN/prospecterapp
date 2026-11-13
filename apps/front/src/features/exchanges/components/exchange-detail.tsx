import { useQuery } from '@tanstack/react-query'
import { MapPin, Pencil, Phone, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment, telUrl } from '@/shared/lib/format'
import { memberName } from '@/shared/lib/member-name'
import { Button } from '@/shared/ui/button'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { DirectionsLink } from '@/shared/ui/directions-link'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/shared/ui/sheet'
import { Skeleton } from '@/shared/ui/skeleton'
import {
  attachTheFiles,
  useAttachFiles,
  useCorrectExchange,
  useCorrectExchangeText,
  useCorrectExchangeVenue,
  useEraseExchange,
  useMarkExchangeDone,
  useMoveExchange,
  usePlanExchange,
  usePlanOrganizationExchange,
} from '../mutations'
import { type ExchangeDetail, exchangeQuery } from '../queries'
import { correctionOf } from '../schema'
import { ExchangeAttachments } from './exchange-attachments'
import { ExchangeForm } from './exchange-form'
import { ExchangeSummary } from './exchange-line'
import { ExchangeText } from './exchange-text'
import { FollowUp } from './follow-up'
import { JoinTheVideoCall } from './join-the-video-call'
import { MoveForm, PlannedExchangeActions, ReportForm } from './planned-exchange-handling'
import { TextCorrectionForm } from './text-correction-form'
import { VenueCorrectionForm } from './venue-correction-form'

type Props = { exchangeId: string | null; onClose: () => void }

/** The whole of an exchange beside the card it was opened from, which stays where it was. */
export function ExchangeDetailSheet({ exchangeId, onClose }: Props) {
  const { t } = useTranslation()

  return (
    <Sheet open={exchangeId !== null} onOpenChange={(open) => !open && onClose()}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-md">
        <SheetHeader>
          <SheetTitle>{t('exchanges.detail.title')}</SheetTitle>
          <SheetDescription className="sr-only">{t('exchanges.detail.description')}</SheetDescription>
        </SheetHeader>
        {exchangeId && <ExchangePanel exchangeId={exchangeId} onErased={onClose} />}
      </SheetContent>
    </Sheet>
  )
}

function ExchangePanel({ exchangeId, onErased }: { exchangeId: string; onErased: () => void }) {
  const exchange = useQuery(exchangeQuery(exchangeId))

  if (!exchange.data) {
    return (
      <div className="space-y-3 px-4">
        <Skeleton className="h-8 w-2/3" />
        <Skeleton className="h-20 w-full" />
      </div>
    )
  }

  return <Exchange exchange={exchange.data} onErased={onErased} />
}

/**
 * What is being done of it: all its facts, its text only, its place or its link corrected, its report written once
 * done, then what follows planned, its moment moved; or nothing while it is read.
 */
type Correcting = 'facts' | 'text' | 'venue' | 'report' | 'followUp' | 'move' | null

function Exchange({ exchange, onErased }: { exchange: ExchangeDetail; onErased: () => void }) {
  const { t } = useTranslation()
  const [correcting, setCorrecting] = useState<Correcting>(null)
  const correct = useCorrectExchange()
  const correctText = useCorrectExchangeText()
  const markDone = useMarkExchangeDone()
  const move = useMoveExchange()
  const attach = useAttachFiles()
  const correctVenue = useCorrectExchangeVenue()
  const params = { path: { id: exchange.id } }
  const mayDeclareConform = Boolean(exchange._links.makeConform)

  if (correcting === 'facts') {
    return (
      <div className="px-4 pb-4">
        <ExchangeForm
          defaultValues={correctionOf(exchange)}
          mayDeclareConform={mayDeclareConform}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correct.mutateAsync({ params, body: { madeConform: false, ...body } })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  if (correcting === 'text') {
    return (
      <div className="px-4 pb-4">
        <TextCorrectionForm
          text={exchange.text}
          mayDeclareConform={mayDeclareConform}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correctText.mutateAsync({ params, body })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  if (correcting === 'report') {
    return (
      <div className="px-4 pb-4">
        <ReportForm
          exchange={exchange}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body, files) => {
            await markDone.mutateAsync({ params, body })
            attachTheFiles(attach, exchange.id, files)
            setCorrecting(exchange.interlocutor.presence === 'forgotten' ? null : 'followUp')
          }}
        />
      </div>
    )
  }

  if (correcting === 'followUp') {
    return (
      <div className="px-4 pb-4">
        <PlanTheFollowUp exchange={exchange} onDone={() => setCorrecting(null)} />
      </div>
    )
  }

  if (correcting === 'move') {
    return (
      <div className="px-4 pb-4">
        <MoveForm
          exchange={exchange}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await move.mutateAsync({ params, body })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  if (correcting === 'venue') {
    return (
      <div className="px-4 pb-4">
        <VenueCorrectionForm
          exchange={exchange}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correctVenue.mutateAsync({ params, body })
            setCorrecting(null)
          }}
        />
      </div>
    )
  }

  return (
    <div className="space-y-4 px-4 pb-4 text-sm">
      <ExchangeSummary
        exchange={exchange}
        interlocutor={exchange.interlocutor}
        linkedToTheCard={Boolean(exchange._links.interlocutor)}
      >
        {exchange.text && (
          <div className="mt-2">
            <ExchangeText
              text={exchange.text}
              flagged={exchange.textFlagged}
              toMakeConform={exchange.textToMakeConform}
              className="whitespace-pre-wrap break-words"
            />
          </div>
        )}
      </ExchangeSummary>
      <Venue exchange={exchange} />
      <div className="space-y-1 text-muted-foreground text-xs">
        <p>
          {t(exchange.status === 'planned' ? 'exchanges.detail.planned' : 'exchanges.detail.noted', {
            moment: formatMoment(exchange.noted.at),
            author: memberName(exchange.noted.by),
          })}
        </p>
        {exchange.corrected && (
          <p>
            {t('exchanges.detail.corrected', {
              moment: formatMoment(exchange.corrected.at),
              author: memberName(exchange.corrected.by),
            })}
          </p>
        )}
        {exchange.madeConform && (
          <p>
            {t('exchanges.detail.madeConform', {
              moment: formatMoment(exchange.madeConform.at),
              author: memberName(exchange.madeConform.by),
            })}
          </p>
        )}
      </div>
      <ExchangeAttachments exchange={exchange} />
      <div className="flex flex-wrap gap-2">
        <PlannedExchangeActions
          exchange={exchange}
          onReport={() => setCorrecting('report')}
          onMove={() => setCorrecting('move')}
        />
        {exchange._links.correctExchange ? (
          <Button variant="outline" onClick={() => setCorrecting('facts')}>
            <Pencil aria-hidden /> {t('exchanges.detail.correct')}
          </Button>
        ) : (
          exchange._links.correctText && (
            <Button variant="outline" onClick={() => setCorrecting('text')}>
              <Pencil aria-hidden /> {t('exchanges.detail.correctText')}
            </Button>
          )
        )}
        {exchange._links.correctVenue && (
          <Button variant="outline" onClick={() => setCorrecting('venue')}>
            <Pencil aria-hidden /> {t(venueCorrectionOf(exchange))}
          </Button>
        )}
        {exchange._links.eraseExchange && <EraseExchange exchangeId={exchange.id} onErased={onErased} />}
      </div>
    </div>
  )
}

/**
 * Done, what follows is planned with the same interlocutor, on the same channel: the contact, or the organization
 * itself; a forgotten contact has nobody to plan with.
 */
function PlanTheFollowUp({ exchange, onDone }: { exchange: ExchangeDetail; onDone: () => void }) {
  const planWithTheContact = usePlanExchange()
  const planWithTheOrganization = usePlanOrganizationExchange()
  const { interlocutor } = exchange

  return (
    <FollowUp
      channel={exchange.channel}
      onDone={onDone}
      plan={(planned) =>
        interlocutor.kind === 'person'
          ? planWithTheContact.mutateAsync({ params: { path: { id: interlocutor.id } }, body: planned })
          : planWithTheOrganization.mutateAsync({
              params: { path: { id: interlocutor.id } },
              body: { personId: null, exchange: planned },
            })
      }
    />
  )
}

/**
 * What is done in one click to hold an exchange to come: the route to the place of a meeting, joining a video call,
 * calling the number of the interlocutor; the place of a meeting shown even once it is done.
 */
function Venue({ exchange }: { exchange: ExchangeDetail }) {
  const { t } = useTranslation()
  const planned = exchange.status === 'planned'
  const numberToCall = planned && exchange.channel === 'call' ? exchange.phoneNumber : null

  if (!exchange.place && !exchange.link && !numberToCall) {
    return null
  }

  return (
    <div className="space-y-2">
      {exchange.place && (
        <p className="flex items-start gap-2">
          <MapPin aria-hidden className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
          <span className="break-words">{exchange.place}</span>
        </p>
      )}
      <div className="flex flex-wrap gap-2">
        {planned && exchange.place && <DirectionsLink to={exchange.place} />}
        <JoinTheVideoCall exchange={exchange} />
        {numberToCall && (
          <Button variant="outline" size="sm" asChild>
            <a href={telUrl(numberToCall)}>
              <Phone aria-hidden /> {t('exchanges.call')}
            </a>
          </Button>
        )}
      </div>
    </div>
  )
}

/** Correcting the place of a meeting, the link of a video call, or adding the link it does not have yet. */
const venueCorrectionOf = (exchange: ExchangeDetail) =>
  exchange.channel === 'meeting'
    ? 'exchanges.detail.correctPlace'
    : exchange.link
      ? 'exchanges.detail.correctLink'
      : 'exchanges.detail.addLink'

/** Erasing an exchange cannot be undone: it is confirmed first. */
function EraseExchange({ exchangeId, onErased }: { exchangeId: string; onErased: () => void }) {
  const { t } = useTranslation()
  const erase = useEraseExchange()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await erase.mutateAsync({ params: { path: { id: exchangeId } } })
    setOpen(false)
    onErased()
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <Trash2 aria-hidden /> {t('exchanges.detail.erase')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('exchanges.detail.eraseTitle')}</DialogTitle>
          <DialogDescription>{t('exchanges.detail.eraseDescription')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={erase.isPending} onClick={() => void confirm()}>
            {t('exchanges.detail.eraseConfirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
