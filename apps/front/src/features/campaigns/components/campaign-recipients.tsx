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
import { type Campaign, campaignRecipientsQuery, type Recipient, type RecipientList } from '../queries'
import { AddRecipients } from './add-recipients'
import { MessagePreview } from './campaign-target'
import { OpenTheExchange } from './open-the-exchange'

type Props = {
  campaign: Campaign
  /** The typed message differs from the saved one. The preview renders only the saved message. */
  changed: boolean
}

/**
 * The recipients that a frozen campaign keeps. Each one shows where the team stands with them, and a cross to
 * remove them. « Ajouter » opens the dialog that adds others. The API renders the saved message for the
 * recipient that is hovered or focused. Once the campaign has started, an erased contact stays among them,
 * without their name. A frozen campaign may lose all its contacts to erasure. It then no longer starts.
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

/**
 * Each recipient with their status, and a cross while they can be removed. A processed recipient links to the
 * exchange logged for them. An erased recipient is named « Contact effacé », with what the team did with
 * them.
 */
function RecipientItems({ campaignId, recipients, previewed, onPreview }: RecipientItemsProps) {
  const { t } = useTranslation()
  const remove = useRemoveRecipient(campaignId)
  const statusLabel = (status: Recipient['status'], missed: boolean) =>
    missed ? t('campaigns.recipients.missed') : t(`campaigns.recipients.status.${status}`)

  return (
    <ul aria-label={t('campaigns.recipients.title')} className="divide-y">
      {recipients.items.map(({ contact, status, missed, _links }, index) =>
        !contact ? (
          // biome-ignore lint/suspicious/noArrayIndexKey: an erased recipient keeps no identity, its place is its key
          <li key={`erased-${index}`} className="flex items-center gap-2">
            <span className="flex-1 px-2 py-2 text-muted-foreground text-sm italic">
              {t('campaigns.recipients.erased')}
            </span>
            <Badge variant="outline">{statusLabel(status, missed)}</Badge>
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
            <Badge variant="secondary">{statusLabel(status, missed)}</Badge>
            <OpenTheExchange exchange={_links.exchange} name={fullNameOf(contact)} />
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
