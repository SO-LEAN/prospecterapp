import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { campaignMessageQuery } from '../queries'
import type { LiveTarget } from '../use-live-target'

type Props = {
  target: LiveTarget
  /** The campaign once prepared, whose message saved the preview renders; none before. */
  campaignId?: string
  /** The message typed differs from the one saved, the only one the preview renders. */
  changed: boolean
}

/**
 * The contacts the campaign targets as it is typed and as their tags are now, and the message of the draft saved,
 * rendered by the api for the contact hovered or focused.
 */
export function CampaignTarget({ target, campaignId, changed }: Props) {
  const { contacts, found, noTarget, loadMore } = target
  const { t } = useTranslation()
  const [previewed, setPreviewed] = useState<string | null>(contacts[0]?.id ?? null)

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>{t('campaigns.target.title')}</CardTitle>
          {noTarget === null && (
            <p className="text-muted-foreground text-sm">{t('campaigns.target.count', { count: found })}</p>
          )}
        </CardHeader>
        <CardContent>
          {noTarget !== null ? (
            <p className="text-muted-foreground text-sm">{t(`campaigns.target.${noTarget}`)}</p>
          ) : contacts.length === 0 ? (
            <p className="text-muted-foreground text-sm">{t('campaigns.target.none')}</p>
          ) : (
            <ul className="divide-y">
              {contacts.map((contact) => (
                <li key={contact.id}>
                  <button
                    type="button"
                    aria-pressed={previewed === contact.id}
                    onMouseEnter={() => setPreviewed(contact.id)}
                    onFocus={() => setPreviewed(contact.id)}
                    className={cn(
                      'flex w-full flex-col items-start rounded-md px-2 py-2 text-left text-sm hover:bg-muted',
                      previewed === contact.id && 'bg-muted',
                    )}
                  >
                    <span className="font-medium">
                      {[contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')}
                    </span>
                    <span className="text-muted-foreground text-xs">
                      {contact.organizations.map((organization) => organization.name).join(', ')}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}
          {loadMore && (
            <Button variant="outline" className="mt-3 w-full" onClick={loadMore}>
              {t('common.loadMore')}
            </Button>
          )}
        </CardContent>
      </Card>
      {campaignId && previewed && (
        <MessagePreview campaignId={campaignId} contactId={previewed} changed={changed} />
      )}
    </div>
  )
}

function MessagePreview({
  campaignId,
  contactId,
  changed,
}: {
  campaignId: string
  contactId: string
  changed: boolean
}) {
  const { t } = useTranslation()
  const message = useQuery(campaignMessageQuery(campaignId, contactId))

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('campaigns.preview.title')}</CardTitle>
        <p className="text-muted-foreground text-sm">
          {changed ? t('campaigns.preview.ofTheSaved') : t('campaigns.preview.hint')}
        </p>
      </CardHeader>
      <CardContent aria-live="polite" className="space-y-2 text-sm">
        {message.data?.subject && (
          <p className="font-medium">{t('campaigns.preview.subject', { subject: message.data.subject })}</p>
        )}
        <p className="whitespace-pre-wrap">{message.data?.text}</p>
      </CardContent>
    </Card>
  )
}
