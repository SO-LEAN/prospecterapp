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
import { useForgetContact } from '../mutations'
import { type Contact, fullNameOf } from '../queries'

type Props = {
  contact: Contact
  onForgotten: () => void
}

/** Erasing a person cannot be undone: it is confirmed first. */
export function ForgetContact({ contact, onForgotten }: Props) {
  const { t } = useTranslation()
  const forget = useForgetContact()
  const [open, setOpen] = useState(false)

  const confirm = async () => {
    await forget.mutateAsync({ params: { path: { id: contact.id } } })
    setOpen(false)
    onForgotten()
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <Trash2 /> {t('contacts.forget.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('contacts.forget.title', { name: fullNameOf(contact) })}</DialogTitle>
          <DialogDescription>{t('contacts.forget.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={forget.isPending} onClick={() => void confirm()}>
            {t('contacts.forget.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
