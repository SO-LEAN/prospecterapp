import { CircleStop, EllipsisVertical, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/shared/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { useDiscardCampaign, useStopCampaign } from '../mutations'
import type { Campaign } from '../queries'

/**
 * What the campaign allows beside its form, as its links say. The member discards it while it has never
 * started, or stops it once it has started. Stopping asks for confirmation first, because a stopped campaign
 * does not start again.
 */
export function CampaignMenu({ campaign, onDiscarded }: { campaign: Campaign; onDiscarded: () => void }) {
  const { t } = useTranslation()
  const discard = useDiscardCampaign()
  const [stopping, setStopping] = useState(false)

  if (!campaign._links.discard && !campaign._links.stop) {
    return null
  }

  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="icon" aria-label={t('campaigns.menu', { name: campaign.name })}>
            <EllipsisVertical aria-hidden />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          {campaign._links.stop && (
            <DropdownMenuItem onSelect={() => setStopping(true)}>
              <CircleStop aria-hidden /> {t('campaigns.stop.action')}
            </DropdownMenuItem>
          )}
          {campaign._links.discard && (
            <DropdownMenuItem
              variant="destructive"
              onSelect={() =>
                discard.mutate({ params: { path: { id: campaign.id } } }, { onSuccess: onDiscarded })
              }
            >
              <Trash2 aria-hidden /> {t('common.remove')}
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
      <StopCampaign campaign={campaign} open={stopping} onOpenChange={setStopping} />
    </>
  )
}

type StopProps = { campaign: Campaign; open: boolean; onOpenChange: (open: boolean) => void }

function StopCampaign({ campaign, open, onOpenChange }: StopProps) {
  const { t } = useTranslation()
  const stop = useStopCampaign()

  const confirm = async () => {
    await stop.mutateAsync({ params: { path: { id: campaign.id } } })
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('campaigns.stop.title')}</DialogTitle>
          <DialogDescription>{t('campaigns.stop.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button disabled={stop.isPending} onClick={() => void confirm().catch(() => undefined)}>
            {t('campaigns.stop.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
