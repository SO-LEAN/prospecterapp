import { Link } from '@tanstack/react-router'
import {
  ArrowDownLeft,
  ArrowUpRight,
  BriefcaseBusiness,
  Building2,
  CalendarDays,
  type LucideIcon,
  Mail,
  MessageCircle,
  MessageSquareText,
  Paperclip,
  Phone,
  PhoneMissed,
  UserRound,
} from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import type { Channel, Exchange, Interlocutor } from '../queries'
import { ExchangeText, PersonalDataWarning } from './exchange-text'

export const CHANNEL_ICONS: Record<Channel, LucideIcon> = {
  call: Phone,
  sms: MessageSquareText,
  email: Mail,
  linkedIn: BriefcaseBusiness,
  meeting: CalendarDays,
  other: MessageCircle,
}

type Summarized = Pick<Exchange, 'channel' | 'direction' | 'missed' | 'occurredAt'>

const directionIconOf = (exchange: Summarized) =>
  exchange.missed ? PhoneMissed : exchange.direction === 'inbound' ? ArrowDownLeft : ArrowUpRight

export const INTERLOCUTOR_ICONS: Record<Interlocutor['kind'], LucideIcon> = {
  organization: Building2,
  person: UserRound,
}

/**
 * One exchange of the timeline: who communicated in the timeline of an organization, its summary, the
 * beginning of its text and how many files are joined to it; what is to make conform once its contact was
 * forgotten.
 */
export function ExchangeLine({
  exchange,
  interlocutor,
}: {
  exchange: Exchange
  interlocutor?: Interlocutor
}) {
  return (
    <ExchangeSummary exchange={exchange} interlocutor={interlocutor}>
      {exchange.text && (
        <ExchangeText
          text={exchange.text}
          flagged={exchange.textFlagged}
          toMakeConform={exchange.textToMakeConform}
          className="truncate"
        />
      )}
      {exchange.toMakeConform && !exchange.textToMakeConform && <ToMakeConform />}
      {exchange.attachmentCount > 0 && <AttachmentCount count={exchange.attachmentCount} />}
    </ExchangeSummary>
  )
}

/**
 * Who communicated, when said, a link to their card when given, its channel, its direction (missed for a call
 * that did not go through) and when, then what follows.
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
  const DirectionIcon = directionIconOf(exchange)
  const direction = exchange.missed ? t('exchanges.missed') : t(`exchanges.directions.${exchange.direction}`)

  return (
    <div className="flex items-start gap-3">
      <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
        <ChannelIcon aria-hidden className="size-4" />
      </span>
      <div className="min-w-0 flex-1">
        {interlocutor && <InterlocutorName interlocutor={interlocutor} linkedToTheCard={linkedToTheCard} />}
        <p className="flex items-center gap-1.5 font-medium">
          {t('exchanges.summary', { channel: t(`exchanges.channels.${exchange.channel}`), direction })}
          <DirectionIcon
            aria-hidden
            className={exchange.missed ? 'size-4 text-destructive' : 'size-4 text-muted-foreground'}
          />
        </p>
        <p className="text-muted-foreground text-xs">
          <time dateTime={exchange.occurredAt}>{formatMoment(exchange.occurredAt)}</time>
        </p>
        {children}
      </div>
    </div>
  )
}

/** Its name, struck through once the contact left the organization; "Contact effacé" once forgotten. */
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
  const name =
    (interlocutor.presence === 'forgotten' ? null : interlocutor.name) ?? t('exchanges.unknownPerson')
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

/** Its files still to make conform, its text made so or none. */
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
