import { Play } from 'lucide-react'
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
  DialogTrigger,
} from '@/shared/ui/dialog'
import { useStartCampaign } from '../mutations'
import type { Campaign } from '../queries'

/** Starting does not undo: its list and its message will no longer change, so the member confirms it first. */
export function StartCampaign({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const start = useStartCampaign()
  const [open, setOpen] = useState(false)

  if (!campaign._links.start) {
    return null
  }

  const confirm = async () => {
    await start.mutateAsync({ params: { path: { id: campaign.id } } })
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Play aria-hidden /> {t('campaigns.start.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('campaigns.start.title')}</DialogTitle>
          <DialogDescription>{t('campaigns.start.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button disabled={start.isPending} onClick={() => void confirm().catch(() => undefined)}>
            {t('campaigns.start.action')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
