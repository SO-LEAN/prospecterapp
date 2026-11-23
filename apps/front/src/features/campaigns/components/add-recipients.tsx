import { useInfiniteQuery } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { contactSlicesQuery, fullNameOf } from '@/features/contacts/queries'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { searchOfTags, taggedContactSlicesQuery } from '@/shared/api/tagged-contacts'
import { Button } from '@/shared/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/shared/ui/dialog'
import { Input } from '@/shared/ui/input'
import { Label } from '@/shared/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/shared/ui/tabs'
import { useAddRecipient, useAddRecipientsByTags } from '../mutations'
import type { Campaign, TargetedContact } from '../queries'
import { targetedTagsOf } from '../schema'

type Props = {
  campaign: Campaign
  /** The contacts that the campaign already keeps. They are left out of what is added. */
  kept: string[]
}

/**
 * « Ajouter » opens a dialog with two tabs. One tab previews the contacts that another tag search finds,
 * without those already kept. The other finds one contact by name. Each tab shows only when the campaign's
 * links allow it.
 */
export function AddRecipients({ campaign, kept }: Props) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  if (!campaign._links.addRecipientsByTags && !campaign._links.addRecipient) {
    return null
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Plus aria-hidden /> {t('campaigns.recipients.add')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('campaigns.recipients.dialog.title')}</DialogTitle>
        </DialogHeader>
        <Tabs defaultValue={campaign._links.addRecipientsByTags ? 'byTags' : 'oneContact'}>
          <TabsList className="w-full">
            {campaign._links.addRecipientsByTags && (
              <TabsTrigger value="byTags">{t('campaigns.recipients.dialog.byTags')}</TabsTrigger>
            )}
            {campaign._links.addRecipient && (
              <TabsTrigger value="oneContact">{t('campaigns.recipients.dialog.oneContact')}</TabsTrigger>
            )}
          </TabsList>
          <TabsContent value="byTags">
            <ByTags campaign={campaign} kept={kept} onAdded={() => setOpen(false)} />
          </TabsContent>
          <TabsContent value="oneContact">
            <OneContact campaign={campaign} kept={kept} />
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  )
}

function ByTags({ campaign, kept, onAdded }: Props & { onAdded: () => void }) {
  const { t } = useTranslation()
  const [chosen, setChosen] = useState<string[]>([])
  const add = useAddRecipientsByTags()
  const found = useInfiniteQuery({
    ...taggedContactSlicesQuery(searchOfTags(targetedTagsOf(chosen, campaign.channel))),
    enabled: chosen.length > 0,
  })
  const loaded = found.data?.pages.flatMap((slice) => slice.items) ?? []
  const toAdd = loaded.filter((contact) => !kept.includes(contact.id))
  const counted = (found.data?.pages[0]?.found ?? 0) - (loaded.length - toAdd.length)
  const wording = found.hasNextPage ? 'atMost' : 'exactly'

  const confirm = async () => {
    await add.mutateAsync({ params: { path: { id: campaign.id } }, body: { tags: chosen } })
    onAdded()
  }

  return (
    <div className="space-y-3">
      <TagsFilter chosen={chosen} onChange={setChosen} />
      {chosen.length === 0 ? (
        <p className="text-muted-foreground text-sm">{t('campaigns.recipients.dialog.chooseTags')}</p>
      ) : counted === 0 ? (
        <p className="text-muted-foreground text-sm">{t('campaigns.recipients.dialog.noneToAdd')}</p>
      ) : (
        <>
          <p className="text-sm">{t(`campaigns.recipients.dialog.toAdd.${wording}`, { count: counted })}</p>
          <ContactLines contacts={toAdd} />
          {found.hasNextPage && (
            <Button variant="outline" className="w-full" onClick={() => void found.fetchNextPage()}>
              {t('common.loadMore')}
            </Button>
          )}
          <Button
            className="w-full"
            disabled={add.isPending}
            onClick={() => void confirm().catch(() => undefined)}
          >
            {t(`campaigns.recipients.dialog.addFound.${wording}`, { count: counted })}
          </Button>
        </>
      )}
    </div>
  )
}

function OneContact({ campaign, kept }: Props) {
  const { t } = useTranslation()
  const searchId = useId()
  const [typed, setTyped] = useState('')
  const add = useAddRecipient()
  const q = typed.trim()
  const found = useInfiniteQuery({ ...contactSlicesQuery(q), enabled: q !== '' })
  const contacts = found.data?.pages.flatMap((slice) => slice.items) ?? []

  return (
    <div className="space-y-3">
      <Label htmlFor={searchId}>{t('campaigns.recipients.dialog.search')}</Label>
      <Input id={searchId} type="search" value={typed} onChange={(event) => setTyped(event.target.value)} />
      <ul className="divide-y">
        {contacts.map((contact) => (
          <li key={contact.id} className="flex items-center justify-between gap-2 py-2 text-sm">
            <span className="font-medium">{fullNameOf(contact)}</span>
            {kept.includes(contact.id) ? (
              <span className="text-muted-foreground text-xs">
                {t('campaigns.recipients.dialog.already')}
              </span>
            ) : (
              <Button
                variant="outline"
                size="sm"
                aria-label={t('campaigns.recipients.dialog.addContact', { name: fullNameOf(contact) })}
                disabled={add.isPending}
                onClick={() =>
                  add.mutate({ params: { path: { id: campaign.id } }, body: { contactId: contact.id } })
                }
              >
                <Plus aria-hidden /> {t('campaigns.recipients.add')}
              </Button>
            )}
          </li>
        ))}
      </ul>
    </div>
  )
}

function ContactLines({ contacts }: { contacts: TargetedContact[] }) {
  return (
    <ul className="max-h-60 divide-y overflow-y-auto">
      {contacts.map((contact) => (
        <li key={contact.id} className="flex flex-col py-2 text-sm">
          <span className="font-medium">{fullNameOf(contact)}</span>
          <span className="text-muted-foreground text-xs">
            {contact.organizations.map((organization) => organization.name).join(', ')}
          </span>
        </li>
      ))}
    </ul>
  )
}
