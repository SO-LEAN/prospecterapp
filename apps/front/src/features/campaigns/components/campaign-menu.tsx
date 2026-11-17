import { EllipsisVertical, Trash2 } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { useDiscardCampaign } from '../mutations'
import type { Campaign } from '../queries'

/** What the campaign offers beside its form: discarding it while a draft, as its links say. */
export function CampaignMenu({ campaign, onDiscarded }: { campaign: Campaign; onDiscarded: () => void }) {
  const { t } = useTranslation()
  const discard = useDiscardCampaign()

  if (!campaign._links.discard) {
    return null
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" aria-label={t('campaigns.menu', { name: campaign.name })}>
          <EllipsisVertical aria-hidden />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem
          variant="destructive"
          onSelect={() =>
            discard.mutate({ params: { path: { id: campaign.id } } }, { onSuccess: onDiscarded })
          }
        >
          <Trash2 aria-hidden /> {t('common.remove')}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
