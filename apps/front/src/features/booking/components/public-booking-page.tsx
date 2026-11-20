import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CardPage } from '@/app/layout/card-page'
import type { components } from '@/shared/api/schema'
import { initials } from '@/shared/lib/format'
import { readerTimeZone } from '@/shared/lib/time-zone'
import { cn } from '@/shared/lib/utils'
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/ui/avatar'
import { Button } from '@/shared/ui/button'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageSkeleton } from '@/shared/ui/page-skeleton'
import { publicBookingPageQuery } from '../queries'
import { appointmentMoment, cityOf, dayLabel, slotTime } from '../slots'
import { BookingForm, type BookingOutcome } from './booking-form'

type PublicPage = components['schemas']['PublicBookingPageResource']
type Host = NonNullable<PublicPage['host']>
type Day = PublicPage['days'][number]

type Props = { code: string }

/**
 * The page a public link leads to, read without signing in (ADR 0153): whom the booker meets, the channels offered, then
 * the free slots of the 14 days to come, on the clock of the visitor. A page offering nothing says nothing else.
 */
export function PublicBookingPage({ code }: Props) {
  const { t } = useTranslation()
  const page = useQuery({ ...publicBookingPageQuery(code), retry: false })

  if (page.isPending) {
    return <PageSkeleton />
  }
  if (page.isError) {
    return <ErrorState error={page.error} reset={() => void page.refetch()} />
  }
  if (!page.data.open || !page.data.host || !page.data.timeZone) {
    return <CardPage title={t('booking.public.title')} description={t('booking.public.noSlot')} />
  }

  return (
    <OpenPage
      code={code}
      onSlotTaken={() => void page.refetch()}
      host={page.data.host}
      channels={page.data.channels}
      hostTimeZone={page.data.timeZone}
      days={page.data.days}
    />
  )
}

type OpenPageProps = {
  code: string
  onSlotTaken: () => void
  host: Host
  channels: PublicPage['channels']
  hostTimeZone: string
  days: Day[]
}

/**
 * The booker chooses a channel, then a slot, then says who they are: the form once booked gives way to what comes next,
 * a slot taken meanwhile sends them back to the slots, read again.
 */
function OpenPage({ code, onSlotTaken, host, channels, hostTimeZone, days }: OpenPageProps) {
  const { t } = useTranslation()
  const [channel, setChannel] = useState(channels.length === 1 ? channels[0] : undefined)
  const [slot, setSlot] = useState<string>()
  const [outcome, setOutcome] = useState<BookingOutcome>()
  const visitorTimeZone = readerTimeZone()

  const done = (ended: BookingOutcome) => {
    setOutcome(ended)
    if (ended.kind === 'slotTaken') {
      setSlot(undefined)
      onSlotTaken()
    }
  }

  if (outcome?.kind === 'booked' && channel) {
    return <Booked host={host} channel={channel} email={outcome.email} phone={outcome.phone} />
  }
  if (outcome?.kind === 'alreadyBooked') {
    return (
      <CardPage
        title={t('booking.public.title')}
        description={t('booking.public.alreadyBooked', {
          moment: appointmentMoment(outcome.appointment, visitorTimeZone),
          host: host.firstName ?? '',
        })}
      />
    )
  }

  return (
    <main className="min-h-dvh bg-background px-4 py-8 sm:px-6">
      <div className="mx-auto grid w-full max-w-5xl gap-8">
        <HostHeader host={host} />
        <fieldset className="grid gap-2">
          <legend className="mb-2 font-medium text-sm">{t('booking.public.channel')}</legend>
          <div className="flex flex-wrap gap-2">
            {channels.map((offered) => (
              <label
                key={offered}
                className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm has-checked:border-primary"
              >
                <input
                  type="radio"
                  name="channel"
                  value={offered}
                  checked={channel === offered}
                  onChange={() => setChannel(offered)}
                />
                {t(`booking.channels.${offered}`)}
              </label>
            ))}
          </div>
        </fieldset>
        <section className="grid gap-4">
          <p className="text-muted-foreground text-sm">
            {visitorTimeZone === hostTimeZone
              ? t('booking.public.hoursOf', { city: cityOf(hostTimeZone) })
              : t('booking.public.hoursConverted', {
                  city: cityOf(visitorTimeZone),
                  hostCity: cityOf(hostTimeZone),
                })}
          </p>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
            {days.map((day) => (
              <DayOfSlots
                key={day.day}
                day={day}
                timeZone={visitorTimeZone}
                choosable={channel !== undefined}
                chosen={slot}
                onChoose={setSlot}
              />
            ))}
          </div>
        </section>
        {outcome?.kind === 'slotTaken' && !slot && (
          <p role="alert" className="font-medium text-destructive text-sm">
            {t('refusals.slotNotFree')}
          </p>
        )}
        {channel && slot && (
          <BookingForm
            key={`${channel}${slot}`}
            code={code}
            channel={channel}
            slot={slot}
            recap={t('booking.public.recap', {
              channel: t(`booking.channels.${channel}`),
              moment: appointmentMoment(slot, visitorTimeZone),
            })}
            onDone={done}
          />
        )}
      </div>
    </main>
  )
}

type BookedProps = { host: Host; channel: PublicPage['channels'][number]; email: string; phone: string }

function Booked({ host, channel, email, phone }: BookedProps) {
  const { t } = useTranslation()

  return (
    <CardPage
      title={t('booking.public.booked.title')}
      description={t('booking.public.booked.invitation', { email })}
    >
      <p className="text-sm">
        {channel === 'call'
          ? t('booking.public.booked.call', { host: host.firstName ?? '', phone })
          : t('booking.public.booked.videoCall')}
      </p>
    </CardPage>
  )
}

function HostHeader({ host }: { host: Host }) {
  const { t } = useTranslation()
  const name = [host.firstName, host.lastName].filter(Boolean).join(' ')

  return (
    <header className="flex items-center gap-4">
      <Avatar className="size-16">
        {host.picture && <AvatarImage src={host.picture.mediumUrl} alt="" />}
        <AvatarFallback className="bg-primary/10 font-medium text-lg text-primary">
          {initials(name)}
        </AvatarFallback>
      </Avatar>
      <div className="grid gap-1">
        <h1 className="font-semibold text-2xl tracking-tight">{name}</h1>
        {host.organization && <p className="text-muted-foreground">{host.organization}</p>}
        <p className="text-sm">{t('booking.public.duration')}</p>
      </div>
    </header>
  )
}

type DayProps = {
  day: Day
  timeZone: string
  choosable: boolean
  chosen?: string
  onChoose: (moment: string) => void
}

function DayOfSlots({ day, timeZone, choosable, chosen, onChoose }: DayProps) {
  const { t } = useTranslation()
  const label = dayLabel(day.day)
  const empty = day.slots.length === 0

  return (
    <fieldset
      aria-label={label}
      className={cn(
        'grid content-start gap-2 rounded-xl border p-3',
        empty && 'bg-muted text-muted-foreground',
      )}
    >
      <legend className="float-left mb-1 font-medium text-sm">{label}</legend>
      {empty ? (
        <span className="text-xs">{t('booking.public.noSlotThatDay')}</span>
      ) : (
        day.slots.map((moment) => (
          <Button
            key={moment}
            type="button"
            size="sm"
            variant={chosen === moment ? 'default' : 'outline'}
            aria-pressed={chosen === moment}
            disabled={!choosable}
            onClick={() => onChoose(moment)}
          >
            {slotTime(moment, timeZone)}
          </Button>
        ))
      )}
    </fieldset>
  )
}
