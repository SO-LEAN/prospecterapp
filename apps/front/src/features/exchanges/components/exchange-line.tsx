import { Link } from '@tanstack/react-router'
import type { TFunction } from 'i18next'
import {
  ArrowDownLeft,
  ArrowUpRight,
  Building2,
  CalendarClock,
  CalendarX2,
  type LucideIcon,
  Paperclip,
  PhoneMissed,
  UserRound,
} from 'lucide-react'
import { type ReactNode, useId } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment, formatUpcoming } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { CreatedByAnAIBadge } from '@/shared/ui/created-by-an-ai-badge'
import { CHANNEL_ICONS } from '@/shared/ui/event/channel'
import { ExchangeMarkers, ToAttachMarker } from '@/shared/ui/event/markers'
import { TypeTile } from '@/shared/ui/event/type-tile'
import { GmailLogo } from '@/shared/ui/gmail-logo'
import { directionWordsOf, type Exchange, type Interlocutor } from '../queries'
import { CampaignOfTheLine } from './campaign-of-the-exchange'
import { ExchangeText, PersonalDataWarning } from './exchange-text'

type Summarized = Pick<
  Exchange,
  'channel' | 'direction' | 'occurredAt' | 'status' | 'canceller' | 'durationInMinutes'
> &
  Partial<Pick<Exchange, 'contactToAttach' | 'importedFrom' | 'createdByAnAI'>>

const STATUS_ICONS: Partial<Record<Exchange['status'], LucideIcon>> = {
  planned: CalendarClock,
  missed: PhoneMissed,
  cancelled: CalendarX2,
}

const iconOf = (exchange: Summarized) =>
  STATUS_ICONS[exchange.status] ?? (exchange.direction === 'inbound' ? ArrowDownLeft : ArrowUpRight)

export const INTERLOCUTOR_ICONS: Record<Interlocutor['kind'], LucideIcon> = {
  organization: Building2,
  person: UserRound,
}

/**
 * One exchange, the same in every timeline: its full moment on the left, its type tile, its title and its markers,
 * then who it is with and their organization. Then the start of its text and how many files are attached to it. Once
 * its contact is forgotten, it shows what is to redact. A click on the line opens its detail. Its actions, its
 * campaign and its markers stay outside the button that opens it.
 */
export function ExchangeLine({
  exchange,
  interlocutor,
  organization,
  onOpen,
  actions,
}: {
  exchange: Exchange
  interlocutor?: Interlocutor
  /** The name of the interlocutor's organization, when the timeline knows it. */
  organization?: string | null
  onOpen?: () => void
  /** What the line offers at hand: its gestures, joining its video call. */
  actions?: ReactNode
}) {
  const { t } = useTranslation()
  const id = useId()
  const parts = { when: `${id}-when`, title: `${id}-title`, who: `${id}-who` }
  const cancelled = exchange.status === 'cancelled'

  return (
    <div className="relative flex flex-wrap items-start gap-x-3 gap-y-2 rounded-row px-2 py-2 transition-colors hover:bg-accent has-[[data-opens]:focus-visible]:ring-[3px] has-[[data-opens]:focus-visible]:ring-ring/50">
      <time
        id={parts.when}
        dateTime={exchange.occurredAt}
        className={cn('w-24 shrink-0 pt-1 text-muted-foreground text-xs', cancelled && 'line-through')}
      >
        {formatMoment(exchange.occurredAt)}
      </time>
      <TypeTile channel={exchange.channel} direction={exchange.direction} />
      <div className="min-w-0 flex-1">
        <p className="flex flex-wrap items-center gap-1.5">
          <LineTitle
            id={parts.title}
            labelledBy={`${parts.when} ${parts.title} ${parts.who}`}
            cancelled={cancelled}
            onOpen={onOpen}
          >
            {titleOf(exchange, t)}
          </LineTitle>
          <span className="relative z-10 inline-flex flex-wrap items-center gap-1.5">
            <ExchangeMarkers exchange={withoutItsCampaign(exchange)} />
            <CampaignOfTheLine exchange={exchange} />
          </span>
        </p>
        {exchange.canceller && (
          <p className="text-muted-foreground text-xs">{t(`exchanges.cancelledBy.${exchange.canceller}`)}</p>
        )}
        <WhoAndWhere id={parts.who} interlocutor={interlocutor} organization={organization} />
        {exchange.text && (
          <ExchangeText
            text={exchange.text}
            flagged={exchange.textFlagged}
            toMakeConform={exchange.textToRedact}
            className="truncate"
          />
        )}
        {exchange.toRedact && !exchange.textToRedact && <ToMakeConform />}
        {exchange.attachmentCount > 0 && <AttachmentCount count={exchange.attachmentCount} />}
      </div>
      {actions && <div className="relative z-10 flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  )
}

/** Its channel and how it went: its direction once done, or its status. */
function titleOf(exchange: Pick<Exchange, 'channel' | 'direction' | 'status'>, t: TFunction) {
  const direction =
    exchange.status === 'done'
      ? t(`exchanges.directions.${directionWordsOf(exchange.channel)}.${exchange.direction}`)
      : t(`exchanges.statuses.${exchange.status}`)

  return t('exchanges.summary', { channel: t(`exchanges.channels.${exchange.channel}`), direction })
}

/** The line's campaign shows apart, as a link to the campaign when the API gives it. */
const withoutItsCampaign = (exchange: Exchange) => ({ ...exchange, campaign: null })

type TitleProps = {
  id: string
  /** The parts of the line that name the button: its moment, its title and who it is with. */
  labelledBy: string
  cancelled: boolean
  onOpen?: () => void
  children: ReactNode
}

/** The line's title. When the line opens a detail, it is the button that covers the whole line. */
function LineTitle({ id, labelledBy, cancelled, onOpen, children }: TitleProps) {
  const className = cn('font-medium', cancelled && 'text-muted-foreground line-through')

  if (!onOpen) {
    return (
      <span id={id} className={className}>
        {children}
      </span>
    )
  }
  return (
    <button
      type="button"
      data-opens
      aria-labelledby={labelledBy}
      onClick={onOpen}
      className={cn(className, "text-left outline-none after:absolute after:inset-0 after:content-['']")}
    >
      <span id={id}>{children}</span>
    </button>
  )
}

type WhoProps = { id: string; interlocutor?: Interlocutor; organization?: string | null }

/**
 * Who the exchange is with and their organization. The line is struck through once the contact has left the
 * organization. The name reads « Contact effacé » once the contact is forgotten.
 */
function WhoAndWhere({ id, interlocutor, organization }: WhoProps) {
  const { t } = useTranslation()
  const removed = interlocutor?.presence === 'removed'
  const name = interlocutor && nameOf(interlocutor, t)
  const who = [name, organization].filter(Boolean).join(' · ')

  if (!who) return null
  return (
    <p id={id} className={cn('truncate text-muted-foreground text-xs', removed && 'line-through')}>
      {interlocutor && <span className="sr-only">{t(`exchanges.interlocutors.${interlocutor.kind}`)}</span>}{' '}
      {who}
      {removed && <span className="sr-only">{t('exchanges.removedContact')}</span>}
    </p>
  )
}

const nameOf = (interlocutor: Interlocutor, t: TFunction) =>
  (interlocutor.presence === 'forgotten' ? null : interlocutor.name) ?? t('exchanges.unknownPerson')

/**
 * Who communicated, when known, with a link to their card when given. Then the exchange's channel and direction, or
 * what became of it (missed, or cancelled and struck through, with who cancelled it when known), and when. Then the
 * content given as its children. A planned exchange shows how soon it comes and how long it lasts.
 */
export function ExchangeSummary({
  exchange,
  interlocutor,
  linkedToTheCard = false,
  children,
}: {
  exchange: Summarized
  interlocutor?: Interlocutor
  linkedToTheCard?: boolean
  children?: ReactNode
}) {
  const { t } = useTranslation()
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]
  const planned = exchange.status === 'planned'
  const cancelled = exchange.status === 'cancelled'
  const StatusIcon = iconOf(exchange)

  return (
    <div className="flex items-start gap-3">
      <span className="relative flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
        <ChannelIcon aria-hidden className="size-4" />
        {exchange.importedFrom === 'google' && <ImportedFromGmail />}
      </span>
      <div className="min-w-0 flex-1">
        {interlocutor && <InterlocutorName interlocutor={interlocutor} linkedToTheCard={linkedToTheCard} />}
        <p
          className={cn(
            'flex items-center gap-1.5 font-medium',
            cancelled && 'text-muted-foreground line-through',
          )}
        >
          {titleOf(exchange, t)}
          <StatusIcon
            aria-hidden
            className={
              exchange.status === 'missed' ? 'size-4 text-destructive' : 'size-4 text-muted-foreground'
            }
          />
          <ToAttachMarker exchange={exchange} />
          {exchange.createdByAnAI && <CreatedByAnAIBadge />}
        </p>
        {exchange.canceller && (
          <p className="text-muted-foreground text-xs">{t(`exchanges.cancelledBy.${exchange.canceller}`)}</p>
        )}
        <p className="text-muted-foreground text-xs">
          {planned ? (
            <time dateTime={exchange.occurredAt}>
              {t('exchanges.lasting', {
                moment: formatUpcoming(exchange.occurredAt),
                duration: formatDuration(exchange.durationInMinutes ?? 0, t),
              })}
            </time>
          ) : (
            <time dateTime={exchange.occurredAt} className={cn(cancelled && 'line-through')}>
              {formatMoment(exchange.occurredAt)}
            </time>
          )}
        </p>
        {children}
      </div>
    </div>
  )
}

const MINUTES_IN_AN_HOUR = 60

/** "30 min", "1 h", "1 h 30". */
export function formatDuration(minutes: number, t: TFunction): string {
  const hours = Math.floor(minutes / MINUTES_IN_AN_HOUR)
  const rest = minutes % MINUTES_IN_AN_HOUR

  if (hours === 0) {
    return t('exchanges.minutes', { count: minutes })
  }
  return rest === 0
    ? t('exchanges.hours', { hours })
    : t('exchanges.hoursAndMinutes', { hours, minutes: String(rest).padStart(2, '0') })
}

/** The interlocutor's name, struck through once the contact has left the organization. It reads "Contact effacé" once the contact is forgotten. */
function InterlocutorName({
  interlocutor,
  linkedToTheCard,
}: {
  interlocutor: Interlocutor
  linkedToTheCard: boolean
}) {
  const { t } = useTranslation()
  const Icon = INTERLOCUTOR_ICONS[interlocutor.kind]
  const removed = interlocutor.presence === 'removed'
  const name = nameOf(interlocutor, t)
  const className = cn('truncate', removed && 'text-muted-foreground line-through')

  return (
    <p className="flex min-w-0 items-center gap-1.5 font-semibold">
      <Icon aria-hidden className="size-4 shrink-0 text-muted-foreground" />
      <span className="sr-only">{t(`exchanges.interlocutors.${interlocutor.kind}`)}</span>
      {linkedToTheCard ? (
        <CardLink interlocutor={interlocutor} className={cn(className, 'hover:underline')}>
          {name}
        </CardLink>
      ) : (
        <span className={className}>{name}</span>
      )}
      {removed && <span className="sr-only">{t('exchanges.removedContact')}</span>}
    </p>
  )
}

type CardLinkProps = { interlocutor: Interlocutor; className: string; children: ReactNode }

function CardLink({ interlocutor, className, children }: CardLinkProps) {
  return interlocutor.kind === 'person' ? (
    <Link to="/contacts/$contactId" params={{ contactId: interlocutor.id }} className={className}>
      {children}
    </Link>
  ) : (
    <Link
      to="/organizations/$organizationId"
      params={{ organizationId: interlocutor.id }}
      className={className}
    >
      {children}
    </Link>
  )
}

/** The Gmail mark on the channel of an exchange imported from Gmail. A hover and a screen reader say it. */
function ImportedFromGmail() {
  const { t } = useTranslation()

  return (
    <span
      title={t('exchanges.importedFromGmail')}
      className="absolute -right-1 -bottom-1 flex size-4 items-center justify-center rounded-full bg-background ring-1 ring-border"
    >
      <GmailLogo className="size-2.5" />
      <span className="sr-only">{t('exchanges.importedFromGmail')}</span>
    </span>
  )
}

/** The exchange's files are still to redact, while its text is redacted or empty. */
function ToMakeConform() {
  const { t } = useTranslation()

  return <PersonalDataWarning warning={t('exchanges.toMakeConform')} />
}

function AttachmentCount({ count }: { count: number }) {
  const { t } = useTranslation()

  return (
    <p className="mt-1 flex items-center gap-1 text-muted-foreground text-xs">
      <Paperclip aria-hidden className="size-3.5" />
      <span aria-hidden>{count}</span>
      <span className="sr-only">{t('exchanges.attachments.count', { count })}</span>
    </p>
  )
}
