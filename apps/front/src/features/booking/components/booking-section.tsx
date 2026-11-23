import { Link } from '@tanstack/react-router'
import { CalendarOff } from 'lucide-react'
import { useId } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { Label } from '@/shared/ui/label'
import { Switch } from '@/shared/ui/switch'
import { type BookingPage, useCloseBooking, useOpenBooking } from '../mutations'
import { ChannelsCard } from './channels-card'
import { InactivePeriodsCard } from './inactive-periods-card'
import { LinkCard } from './link-card'
import { WindowsCard } from './windows-card'

type Props = { page: BookingPage }

/** My booking page in the settings (ADR 0148): its switch, its windows, its inactive periods, its channels and its link. */
export function BookingSection({ page }: Props) {
  return (
    <div className="space-y-6">
      {!page.calendarConnected && <NoCalendar />}
      <OpenSwitch page={page} />
      <WindowsCard key={JSON.stringify(page.windows)} page={page} />
      <InactivePeriodsCard page={page} />
      <ChannelsCard page={page} />
      <LinkCard page={page} />
    </div>
  )
}

function NoCalendar() {
  const { t } = useTranslation()

  return (
    <Alert>
      <CalendarOff aria-hidden />
      <AlertTitle>{t('booking.noCalendar.title')}</AlertTitle>
      <AlertDescription>
        <p>{t('booking.noCalendar.description')}</p>
        <Button asChild variant="link" className="h-auto p-0">
          <Link to="/settings" search={{ section: 'connectors' }}>
            {t('booking.noCalendar.action')}
          </Link>
        </Button>
      </AlertDescription>
    </Alert>
  )
}

/** Opens or closes the page at once, as the page allows. */
function OpenSwitch({ page }: Props) {
  const { t } = useTranslation()
  const switchId = useId()
  const openBooking = useOpenBooking()
  const closeBooking = useCloseBooking()
  const pending = openBooking.isPending || closeBooking.isPending
  const switchable = page.open
    ? Boolean(page._links.close)
    : Boolean(page._links.open) && page.calendarConnected

  const toggle = (open: boolean) =>
    (open ? openBooking : closeBooking).mutate(
      {},
      { onSuccess: () => toast.success(t(open ? 'booking.opened' : 'booking.shut')) },
    )

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('booking.title')}</CardTitle>
        <CardDescription>{t('booking.description')}</CardDescription>
      </CardHeader>
      <CardContent className="flex items-center justify-between gap-4 text-sm">
        <Label htmlFor={switchId} className="text-base">
          {t(page.open ? 'booking.open' : 'booking.closed')}
        </Label>
        <Switch
          id={switchId}
          checked={page.open}
          disabled={!switchable || pending}
          onCheckedChange={toggle}
        />
      </CardContent>
    </Card>
  )
}
