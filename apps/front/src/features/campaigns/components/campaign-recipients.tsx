import { useSuspenseQuery } from '@tanstack/react-query'
import { X } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { fullNameOf } from '@/features/contacts/queries'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { useRemoveRecipient } from '../mutations'
import { type Campaign, campaignRecipientsQuery, type RecipientList } from '../queries'
import { AddRecipients } from './add-recipients'
import { MessagePreview } from './campaign-target'

type Props = {
  campaign: Campaign
  /** The message typed differs from the one saved, the only one the preview renders. */
  changed: boolean
}

/**
 * The recipients a frozen campaign keeps, each with where the team is with them and its cross, « Ajouter » opening
 * the dialog adding others, and the message saved rendered by the api for the recipient hovered or focused; once
 * started, a contact erased stays among them, without its name. Frozen, the erasure of its contacts may leave none:
 * it no longer starts.
 */
export function CampaignRecipients({ campaign, changed }: Props) {
  const { t } = useTranslation()
  const { data: recipients } = useSuspenseQuery(campaignRecipientsQuery(campaign.id))
  const kept = recipients.items.flatMap(({ contact }) => (contact ? [contact.id] : []))
  const [hovered, setPreviewed] = useState<string | null>(null)
  const previewed = hovered !== null && kept.includes(hovered) ? hovered : (kept[0] ?? null)

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-row items-start justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle>
              <h2>{t('campaigns.recipients.title')}</h2>
            </CardTitle>
            <p className="text-muted-foreground text-sm">
              {t('campaigns.recipients.count', { count: recipients.items.length })}
            </p>
          </div>
          <AddRecipients campaign={campaign} kept={kept} />
        </CardHeader>
        <CardContent>
          {recipients.items.length === 0 ? (
            <p className="text-muted-foreground text-sm">{t('campaigns.recipients.emptied')}</p>
          ) : (
            <RecipientItems
              campaignId={campaign.id}
              recipients={recipients}
              previewed={previewed}
              onPreview={setPreviewed}
            />
          )}
        </CardContent>
      </Card>
      {previewed && <MessagePreview campaignId={campaign.id} contactId={previewed} changed={changed} />}
    </div>
  )
}

type RecipientItemsProps = {
  campaignId: string
  recipients: RecipientList
  previewed: string | null
  onPreview: (contactId: string) => void
}

/** Each recipient with its status, its cross while it may be taken off; one erased named « Contact effacé ». */
function RecipientItems({ campaignId, recipients, previewed, onPreview }: RecipientItemsProps) {
  const { t } = useTranslation()
  const remove = useRemoveRecipient(campaignId)

  return (
    <ul aria-label={t('campaigns.recipients.title')} className="divide-y">
      {recipients.items.map(({ contact, status, _links }, index) =>
        !contact ? (
          // biome-ignore lint/suspicious/noArrayIndexKey: an erased recipient keeps no identity, its place is its key
          <li key={`erased-${index}`} className="flex items-center gap-2">
            <span className="flex-1 px-2 py-2 text-muted-foreground text-sm italic">
              {t('campaigns.recipients.erased')}
            </span>
            <Badge variant="outline">{t(`campaigns.recipients.status.${status}`)}</Badge>
          </li>
        ) : (
          <li key={contact.id} className="flex items-center gap-2">
            <button
              type="button"
              aria-pressed={previewed === contact.id}
              onMouseEnter={() => onPreview(contact.id)}
              onFocus={() => onPreview(contact.id)}
              className={cn(
                'flex min-w-0 flex-1 flex-col items-start rounded-md px-2 py-2 text-left text-sm hover:bg-muted',
                previewed === contact.id && 'bg-muted',
              )}
            >
              <span className="font-medium">{fullNameOf(contact)}</span>
              <span className="text-muted-foreground text-xs">
                {contact.organizations.map((organization) => organization.name).join(', ')}
              </span>
            </button>
            <Badge variant="secondary">{t(`campaigns.recipients.status.${status}`)}</Badge>
            {_links.remove && (
              <Button
                variant="ghost"
                size="icon"
                aria-label={t('campaigns.recipients.remove', { name: fullNameOf(contact) })}
                disabled={remove.isPending}
                onClick={() => remove.mutate({ params: { path: { id: campaignId, contactId: contact.id } } })}
              >
                <X aria-hidden />
              </Button>
            )}
          </li>
        ),
      )}
    </ul>
  )
}
