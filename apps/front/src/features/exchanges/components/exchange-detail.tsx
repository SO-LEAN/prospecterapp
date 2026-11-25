import { useQuery } from '@tanstack/react-query'
import type { TFunction } from 'i18next'
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
import { GmailLogo } from '@/shared/ui/gmail-logo'
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
import { CampaignOfTheExchange } from './campaign-of-the-exchange'
import { ExchangeAttachments } from './exchange-attachments'
import { ExchangeForm } from './exchange-form'
import { ExchangeSummary } from './exchange-line'
import { ExchangeText } from './exchange-text'
import { FollowUp } from './follow-up'
import { GoogleSync } from './google-sync'
import { JoinTheVideoCall } from './join-the-video-call'
import { MoveForm, PlannedExchangeActions, ReportForm } from './planned-exchange-handling'
import { TextCorrectionForm } from './text-correction-form'
import { VenueCorrectionForm } from './venue-correction-form'

type Props = { exchangeId: string | null; onClose: () => void }

/** The whole exchange, beside the card it was opened from. That card stays where it was. */
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

type PanelProps = {
  exchangeId: string
  onErased: () => void
  /** The dashboard opens it on its report, to mark it done at once, or on its move. */
  startWith?: StartWith
}

export type StartWith = 'report' | 'move'

/** What the panel opens on, when the exchange offers it. */
function offered(startWith: StartWith | undefined, { _links }: ExchangeDetail): StartWith | null {
  if (startWith === 'report') return _links.markDone ? startWith : null
  if (startWith === 'move') return _links.move ? startWith : null
  return null
}

/** The whole exchange and what it offers, read from the API. It shows in its sheet, in the calendar or in the day. */
export function ExchangePanel({ exchangeId, onErased, startWith }: PanelProps) {
  const exchange = useQuery(exchangeQuery(exchangeId))

  if (!exchange.data) {
    return (
      <div className="space-y-3 px-4">
        <Skeleton className="h-8 w-2/3" />
        <Skeleton className="h-20 w-full" />
      </div>
    )
  }

  return <Exchange exchange={exchange.data} onErased={onErased} startWith={startWith} />
}

/**
 * What the member is doing with the exchange: correcting all its facts, its text only, or its place or link. Or
 * writing its report once it is done, then planning what follows, or moving its moment. Null while they only read it.
 */
type Correcting = 'facts' | 'text' | 'venue' | 'report' | 'followUp' | 'move' | null

/**
 * The exchange's revision when the current correction began, kept while it goes on. The correction is sent with that
 * revision, not with one read since. So a change made meanwhile is refused instead of overwritten (ADR 0187).
 */
function useRevisionWhenOpened(correcting: Correcting, revision: number): number {
  const [opened, setOpened] = useState({ correcting, revision })

  if (opened.correcting !== correcting) {
    setOpened({ correcting, revision })
    return revision
  }

  return opened.revision
}

function Exchange({
  exchange,
  onErased,
  startWith,
}: { exchange: ExchangeDetail } & Omit<PanelProps, 'exchangeId'>) {
  const { t } = useTranslation()
  const [correcting, setCorrecting] = useState<Correcting>(offered(startWith, exchange))
  const correct = useCorrectExchange()
  const correctText = useCorrectExchangeText()
  const markDone = useMarkExchangeDone()
  const move = useMoveExchange()
  const attach = useAttachFiles()
  const correctVenue = useCorrectExchangeVenue()
  const params = { path: { id: exchange.id } }
  const onTheRevisionRead = {
    ...params,
    query: { revision: useRevisionWhenOpened(correcting, exchange.revision) },
  }
  const mayDeclareConform = Boolean(exchange._links.redact)

  if (correcting === 'facts') {
    return (
      <div className="px-4 pb-4">
        <ExchangeForm
          defaultValues={correctionOf(exchange)}
          mayDeclareConform={mayDeclareConform}
          onCancel={() => setCorrecting(null)}
          onSubmit={async (body) => {
            await correct.mutateAsync({ params: onTheRevisionRead, body: { redacted: false, ...body } })
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
            await correctText.mutateAsync({ params: onTheRevisionRead, body })
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
            await correctVenue.mutateAsync({ params: onTheRevisionRead, body })
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
              toMakeConform={exchange.textToRedact}
              className="whitespace-pre-wrap break-words"
            />
          </div>
        )}
      </ExchangeSummary>
      {exchange.campaign && (
        <CampaignOfTheExchange campaign={exchange.campaign} linked={Boolean(exchange._links.campaign)} />
      )}
      <Venue exchange={exchange} />
      {exchange.status === 'planned' && <GoogleSync exchangeId={exchange.id} />}
      <div className="space-y-1 text-muted-foreground text-xs">
        <p>{originOf(exchange, t)}</p>
        {exchange.corrected && (
          <p>
            {t('exchanges.detail.corrected', {
              moment: formatMoment(exchange.corrected.at),
              author: memberName(exchange.corrected.by),
            })}
          </p>
        )}
        {exchange.redacted && (
          <p>
            {t('exchanges.detail.madeConform', {
              moment: formatMoment(exchange.redacted.at),
              author: memberName(exchange.redacted.by),
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
        {exchange._links.openInMailbox && (
          <Button variant="outline" asChild>
            <a href={exchange._links.openInMailbox.href} target="_blank" rel="noreferrer">
              <GmailLogo className="size-4" /> {t('exchanges.detail.openInGmail')}
            </a>
          </Button>
        )}
        {exchange._links.eraseExchange && <EraseExchange exchangeId={exchange.id} onErased={onErased} />}
      </div>
    </div>
  )
}

/** A member plans an exchange that is to come or was cancelled. A member notes one that took place or was missed. */
const MEMBER_ORIGIN_BY_STATUS = {
  planned: 'exchanges.detail.planned',
  cancelled: 'exchanges.detail.plannedThenCancelled',
  done: 'exchanges.detail.noted',
  missed: 'exchanges.detail.noted',
} as const satisfies Record<ExchangeDetail['status'], string>

/**
 * Where the exchange came from: booked through the public link, imported from Gmail, or planned or logged by a member.
 */
function originOf(exchange: ExchangeDetail, t: TFunction): string {
  const moment = formatMoment(exchange.noted.at)
  if (exchange.bookedByLink) return t('exchanges.detail.booked', { moment })
  if (exchange.importedFrom === 'google') return t('exchanges.detail.importedFromGmail', { moment })
  return t(MEMBER_ORIGIN_BY_STATUS[exchange.status], { moment, author: memberName(exchange.noted.by) })
}

/**
 * Once the exchange is done, plans what follows with the same interlocutor, on the same channel. The interlocutor is
 * the contact or the organization itself. A forgotten contact leaves nobody to plan with.
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
 * What one click does to hold an upcoming exchange: show the route to a meeting's place, join a video call, or call
 * the interlocutor's number. A meeting's place shows even once the meeting is done.
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

/** Corrects a meeting's place or a video call's link, or adds the link the video call does not have yet. */
const venueCorrectionOf = (exchange: ExchangeDetail) =>
  exchange.channel === 'meeting'
    ? 'exchanges.detail.correctPlace'
    : exchange.link
      ? 'exchanges.detail.correctLink'
      : 'exchanges.detail.addLink'

/** Erasing an exchange cannot be undone, so the member confirms it first. */
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
