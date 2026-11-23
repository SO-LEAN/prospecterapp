import { Copy, ExternalLink, QrCode, RefreshCw } from 'lucide-react'
import QRCode from 'qrcode'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
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
import { Field } from '@/shared/ui/form/field'
import { Input } from '@/shared/ui/input'
import { type BookingPage, useChangeBookingLink } from '../mutations'
import { publicLinkOf } from '../public-link'

type Props = { page: BookingPage }

/** My public link: I can copy it, preview it, download it as a QR code, and change it after a confirmation. There is no link before the first opening. */
export function LinkCard({ page }: Props) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('booking.link.title')}</CardTitle>
        <CardDescription>{t('booking.link.description')}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {page.code ? (
          <TheLink address={publicLinkOf(page.code)} />
        ) : (
          <p className="text-muted-foreground">{t('booking.link.none')}</p>
        )}
        {page._links.changeLink && <ChangeLinkButton />}
      </CardContent>
    </Card>
  )
}

function TheLink({ address }: { address: string }) {
  const { t } = useTranslation()
  const copy = async () => {
    await navigator.clipboard.writeText(address)
    toast.success(t('booking.link.copied'))
  }

  return (
    <div className="space-y-3">
      <Field label={t('booking.link.address')}>
        <Input readOnly value={address} onFocus={(event) => event.target.select()} />
      </Field>
      <div className="flex flex-wrap gap-2">
        <Button variant="outline" onClick={() => void copy()}>
          <Copy aria-hidden /> {t('booking.link.copy')}
        </Button>
        <Button asChild variant="outline">
          <a href={address} target="_blank" rel="noopener noreferrer">
            <ExternalLink aria-hidden /> {t('booking.link.preview')}
          </a>
        </Button>
        <QrCodeButton address={address} />
      </div>
    </div>
  )
}

/** The link's QR code, drawn in the browser as a PNG. The download is that image as it is. */
function QrCodeButton({ address }: { address: string }) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const [image, setImage] = useState<string | null>(null)

  useEffect(() => {
    if (!open) {
      return
    }
    let current = true
    void QRCode.toDataURL(address, { width: 512, margin: 2 }).then((drawn) => {
      if (current) {
        setImage(drawn)
      }
    })
    return () => {
      current = false
    }
  }, [open, address])

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <QrCode aria-hidden /> {t('booking.link.qrCode')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('booking.link.qrCode')}</DialogTitle>
          <DialogDescription>{address}</DialogDescription>
        </DialogHeader>
        {image && <img src={image} alt={t('booking.link.qrCodeOf')} className="mx-auto size-64" />}
        <DialogFooter>
          {image && (
            <Button asChild>
              <a href={image} download="prise-de-rendez-vous.png">
                {t('booking.link.download')}
              </a>
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

function ChangeLinkButton() {
  const { t } = useTranslation()
  const changeLink = useChangeBookingLink()
  const [open, setOpen] = useState(false)

  const changed = () => {
    setOpen(false)
    toast.success(t('booking.link.change.done'))
  }

  const confirm = () => changeLink.mutate({}, { onSuccess: changed })

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost">
          <RefreshCw aria-hidden /> {t('booking.link.change.action')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('booking.link.change.title')}</DialogTitle>
          <DialogDescription>{t('booking.link.change.description')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={changeLink.isPending} onClick={confirm}>
            {t('booking.link.change.confirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
