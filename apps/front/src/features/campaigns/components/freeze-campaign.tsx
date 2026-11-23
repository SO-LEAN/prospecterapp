import { Lock } from 'lucide-react'
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
import { useFreezeCampaign } from '../mutations'
import type { Campaign } from '../queries'

/**
 * Freezing cannot be undone: the campaign's search will no longer change. So the member confirms it first.
 */
export function FreezeCampaign({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const freeze = useFreezeCampaign()
  const [open, setOpen] = useState(false)

  if (!campaign._links.freeze) {
    return null
  }

  const confirm = async () => {
    await freeze.mutateAsync({ params: { path: { id: campaign.id } } })
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Lock aria-hidden /> {t('campaigns.freeze.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('campaigns.freeze.title')}</DialogTitle>
          <DialogDescription>{t('campaigns.freeze.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button disabled={freeze.isPending} onClick={() => void confirm().catch(() => undefined)}>
            {t('campaigns.freeze.action')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
