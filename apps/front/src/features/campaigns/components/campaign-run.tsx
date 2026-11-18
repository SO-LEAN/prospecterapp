import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery, useSuspenseQuery } from '@tanstack/react-query'
import { Check, Copy, ExternalLink } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { fullNameOf } from '@/features/contacts/queries'
import { directionWordsOf, isAgreedUpon } from '@/features/exchanges/queries'
import { localMoment } from '@/features/exchanges/schema'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { cn } from '@/shared/lib/utils'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import { useDismissRecipient, useNoteCampaignExchange } from '../mutations'
import {
  type Campaign,
  type CampaignMessage,
  campaignMessageQuery,
  campaignRecipientsQuery,
  type Recipient,
} from '../queries'
import { meansOf, openingOf } from '../reach'
import { CampaignSummary } from './campaign-summary'

type Contact = NonNullable<Recipient['contact']>
type Handled = 'processed' | 'dismissed'

/** The key moving to the next recipient to handle without noting anything. */
const NEXT_KEY = 'n'
const MAX_TEXT = 5000
const MAX_REASON = 500

/**
 * A started campaign led as a list of tasks (ADR 0145): at the left its recipients, those to handle first; at the
 * right the one chosen, how to reach them, the message rendered for them, and the exchange to note or their
 * dismissal; its summary once nobody is left to handle. Campaigns processes a recipient a moment after their
 * exchange is noted: the page holds them handled at once and moves to the next one to handle, the campaign read
 * again behind.
 */
export function CampaignRun({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const { data: recipients } = useSuspenseQuery(campaignRecipientsQuery(campaign.id))
  const [handled, setHandled] = useState<ReadonlyMap<string, Handled>>(new Map())
  const [chosen, choose] = useState<string | null>(null)
  const isToHandle = (recipient: Recipient) =>
    recipient.contact !== null &&
    recipient.contact !== undefined &&
    recipient._links.noteExchange !== undefined &&
    !handled.has(recipient.contact.id)
  const toHandle = recipients.items.filter(isToHandle).flatMap(({ contact }) => (contact ? [contact] : []))
  const ordered = [
    ...recipients.items.filter(isToHandle),
    ...recipients.items.filter((recipient) => !isToHandle(recipient)),
  ]
  const current = toHandle.find((contact) => contact.id === chosen) ?? toHandle[0] ?? null

  const nextAfter = (contactId: string) => {
    const following = toHandle[toHandle.findIndex((contact) => contact.id === contactId) + 1]
    const first = toHandle.find((contact) => contact.id !== contactId)
    choose((following ?? first)?.id ?? null)
  }
  const markHandled = (contactId: string, how: Handled) => {
    setHandled((before) => new Map(before).set(contactId, how))
    nextAfter(contactId)
  }

  useEffect(() => {
    const skip = (event: KeyboardEvent) => {
      if (
        event.key !== NEXT_KEY ||
        isTyping(event.target) ||
        event.ctrlKey ||
        event.metaKey ||
        event.altKey ||
        !current
      ) {
        return
      }
      nextAfter(current.id)
    }
    window.addEventListener('keydown', skip)
    return () => window.removeEventListener('keydown', skip)
  })

  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
      <Card>
        <CardHeader>
          <CardTitle>
            <h2>{t('campaigns.recipients.title')}</h2>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <ul aria-label={t('campaigns.recipients.title')} className="divide-y">
            {ordered.map((recipient, index) => (
              <RecipientLine
                key={recipient.contact?.id ?? `erased-${index}`}
                recipient={recipient}
                status={(recipient.contact && handled.get(recipient.contact.id)) || recipient.status}
                current={recipient.contact?.id === current?.id}
                onChoose={isToHandle(recipient) ? choose : undefined}
              />
            ))}
          </ul>
        </CardContent>
      </Card>
      {current ? (
        <RecipientPanel
          key={current.id}
          campaign={campaign}
          contact={current}
          onHandled={(how) => markHandled(current.id, how)}
          onNext={toHandle.length > 1 ? () => nextAfter(current.id) : undefined}
        />
      ) : (
        <CampaignSummary campaign={campaign} />
      )}
    </div>
  )
}

function isTyping(target: EventTarget | null): boolean {
  return (
    target instanceof HTMLElement &&
    (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
  )
}

function RecipientLine({
  recipient,
  status,
  current,
  onChoose,
}: {
  recipient: Recipient
  status: Recipient['status']
  current: boolean
  onChoose?: (contactId: string) => void
}) {
  const { t } = useTranslation()
  const { contact } = recipient
  const statusLabel =
    recipient.missed && status === 'processed'
      ? t('campaigns.recipients.missed')
      : t(`campaigns.recipients.status.${status}`)

  if (!contact) {
    return (
      <li className="flex items-center gap-2">
        <span className="flex-1 px-2 py-2 text-muted-foreground text-sm italic">
          {t('exchanges.unknownPerson')}
        </span>
        <Badge variant="outline">{statusLabel}</Badge>
      </li>
    )
  }

  const who = (
    <>
      <span className="font-medium">{fullNameOf(contact)}</span>
      <span className="text-muted-foreground text-xs">
        {contact.organizations.map((organization) => organization.name).join(', ')}
      </span>
    </>
  )

  return (
    <li className="flex items-center gap-2">
      {onChoose ? (
        <button
          type="button"
          aria-current={current ? 'true' : undefined}
          onClick={() => onChoose(contact.id)}
          className={cn(
            'flex min-w-0 flex-1 flex-col items-start rounded-md px-2 py-2 text-left text-sm hover:bg-muted',
            current && 'bg-muted',
          )}
        >
          {who}
        </button>
      ) : (
        <span className="flex min-w-0 flex-1 flex-col items-start px-2 py-2 text-sm">{who}</span>
      )}
      <Badge variant={status === 'pending' ? 'default' : 'secondary'}>{statusLabel}</Badge>
    </li>
  )
}

function RecipientPanel({
  campaign,
  contact,
  onHandled,
  onNext,
}: {
  campaign: Campaign
  contact: Contact
  onHandled: (how: Handled) => void
  onNext?: () => void
}) {
  const { t } = useTranslation()
  const message = useQuery(campaignMessageQuery(campaign.id, contact.id))
  const means = meansOf(campaign.channel, contact.contactDetails)

  return (
    <Card aria-label={fullNameOf(contact)}>
      <CardHeader className="flex flex-row items-start justify-between gap-2">
        <div className="space-y-1">
          <CardTitle>
            <h2>{fullNameOf(contact)}</h2>
          </CardTitle>
          <p className="text-muted-foreground text-sm">
            {contact.organizations.map((organization) => organization.name).join(', ')}
          </p>
          {means && (
            <p className="text-sm">
              <span className="text-muted-foreground">{t(`campaigns.run.means.${campaign.channel}`)} : </span>
              {means}
            </p>
          )}
        </div>
        {onNext && (
          <Button variant="ghost" aria-keyshortcuts={NEXT_KEY} onClick={onNext}>
            {t('campaigns.run.next')}
            <kbd
              aria-hidden
              className="rounded border px-1.5 font-mono text-muted-foreground text-xs uppercase"
            >
              {NEXT_KEY}
            </kbd>
          </Button>
        )}
      </CardHeader>
      <CardContent className="space-y-6">
        {message.data && <RenderedMessage campaign={campaign} contact={contact} message={message.data} />}
        {!message.isPending && (
          <RecipientForm campaign={campaign} contact={contact} message={message.data} onHandled={onHandled} />
        )}
      </CardContent>
    </Card>
  )
}

function RenderedMessage({
  campaign,
  contact,
  message,
}: {
  campaign: Campaign
  contact: Contact
  message: CampaignMessage
}) {
  const { t } = useTranslation()
  const opening = openingOf(campaign.channel, contact.contactDetails, message)
  const [copied, setCopied] = useState(false)
  const copy = async () => {
    await navigator.clipboard.writeText(message.text)
    setCopied(true)
  }

  return (
    <section aria-label={t('campaigns.run.message')} className="space-y-2 rounded-md border p-3 text-sm">
      {message.subject && (
        <p className="font-medium">{t('campaigns.preview.subject', { subject: message.subject })}</p>
      )}
      <p className="whitespace-pre-wrap">{message.text}</p>
      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="outline" size="sm" onClick={copy}>
          {copied ? <Check aria-hidden /> : <Copy aria-hidden />}
          {t('campaigns.run.copy')}
        </Button>
        {opening && (
          <Button variant="outline" size="sm" asChild>
            <a
              href={opening.href}
              {...(opening.external && { target: '_blank', rel: 'noopener noreferrer' })}
            >
              <ExternalLink aria-hidden />
              {t('campaigns.run.open')}
            </a>
          </Button>
        )}
      </div>
    </section>
  )
}

const isPast = (moment: string) => new Date(moment).getTime() <= Date.now()

/** Mirrors `CampaignExchangeInput` and `DismissalInput` of the API: the moment never in the future. */
const recipientSchema = z.object({
  occurredAt: z
    .string()
    .min(1, { error: () => i18n.t('validation.required') })
    .refine(isPast, { error: () => i18n.t('exchanges.inTheFuture') }),
  text: text(MAX_TEXT),
  missed: z.boolean(),
  reason: text(MAX_REASON),
})

type RecipientValues = z.input<typeof recipientSchema>

const recipientFields = ['occurredAt', 'text', 'missed', 'reason'] as const

/**
 * « Noter un échange » prefilled: the channel of the campaign, sent, now, the message rendered as its text; saved, the
 * recipient is processed; dismissed instead, without an exchange, for the reason typed.
 */
function RecipientForm({
  campaign,
  contact,
  message,
  onHandled,
}: {
  campaign: Campaign
  contact: Contact
  message?: CampaignMessage
  onHandled: (how: Handled) => void
}) {
  const { t } = useTranslation()
  const note = useNoteCampaignExchange(campaign.id)
  const dismiss = useDismissRecipient(campaign.id)
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<RecipientValues>({
    resolver: zodResolver(recipientSchema),
    defaultValues: {
      occurredAt: localMoment(new Date()),
      text: message?.text ?? '',
      missed: false,
      reason: '',
    },
  })
  const { isSubmitting } = useFormState({ control: form.control })
  const path = { id: campaign.id, contactId: contact.id }
  const refused = (error: unknown) => setGlobalErrors(applyViolations(error, form.setError, recipientFields))

  const save = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await note.mutateAsync({
        params: { path },
        body: {
          occurredAt: new Date(values.occurredAt).toISOString(),
          text: orNull(values.text),
          missed: isAgreedUpon(campaign.channel) && values.missed,
        },
      })
      onHandled('processed')
    } catch (error) {
      refused(error)
    }
  })

  const dismissIt = async () => {
    setGlobalErrors([])
    try {
      await dismiss.mutateAsync({ params: { path }, body: { reason: orNull(form.getValues('reason')) } })
      onHandled('dismissed')
    } catch (error) {
      refused(error)
    }
  }

  return (
    <form onSubmit={save} noValidate aria-label={t('campaigns.run.note')} className="space-y-4">
      <h3 className="font-medium text-sm">{t('campaigns.run.note')}</h3>
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <p className="text-sm">
        {t(`exchanges.channels.${campaign.channel}`)} ·{' '}
        {t(`exchanges.directions.${directionWordsOf(campaign.channel)}.outbound`)}
      </p>
      {isAgreedUpon(campaign.channel) && (
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="size-4 accent-primary" {...form.register('missed')} />
          {t('exchanges.missedLabel')}
        </label>
      )}
      <FormField form={form} name="occurredAt" label={t('exchanges.occurredAt')}>
        <Input type="datetime-local" max={localMoment(new Date())} {...form.register('occurredAt')} />
      </FormField>
      <FormField form={form} name="text" label={t('exchanges.text')} optional={t('common.optional')}>
        <Textarea rows={5} {...form.register('text')} />
      </FormField>
      <FormField form={form} name="reason" label={t('campaigns.run.reason')} optional={t('common.optional')}>
        <Input {...form.register('reason')} />
      </FormField>
      <div className="flex justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          disabled={isSubmitting || dismiss.isPending}
          onClick={dismissIt}
        >
          {t('campaigns.run.dismiss')}
        </Button>
        <Button type="submit" disabled={isSubmitting || dismiss.isPending}>
          {isSubmitting ? t('common.saving') : t('common.save')}
        </Button>
      </div>
    </form>
  )
}
