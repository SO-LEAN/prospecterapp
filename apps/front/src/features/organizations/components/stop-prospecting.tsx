import { Trash2 } from 'lucide-react'
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
import { useStopProspecting } from '../mutations'
import type { Organization } from '../queries'

type Props = {
  organization: Organization
  onStopped: () => void
}

/** Stopping to prospect erases the contacts no other organization names: it is confirmed first. */
export function StopProspecting({ organization, onStopped }: Props) {
  const { t } = useTranslation()
  const stop = useStopProspecting()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await stop.mutateAsync({ params: { path: { id: organization.id } } })
    setOpen(false)
    onStopped()
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <Trash2 /> {t('organizations.stop.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('organizations.stop.title', { name: organization.name })}</DialogTitle>
          <DialogDescription>{t('organizations.stop.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={stop.isPending} onClick={() => void confirm()}>
            {t('organizations.stop.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
