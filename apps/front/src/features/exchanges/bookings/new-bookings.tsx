import { useQuery } from '@tanstack/react-query'
import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { formatMoment } from '@/shared/lib/format'
import { addDays, startOfDay } from '../calendar/dates'
import { type CalendarExchange, calendarQuery } from '../calendar/queries'

/** How often my agenda is read again while the application is open, as for the reminders. */
export const READ_AGAIN_MS = 5 * 60_000

/** The read period reaches beyond the 14 days, from tomorrow, in which a public link offers slots. */
const DAYS_A_LINK_OFFERS = 16

const bookedOf = (items: CalendarExchange[]) =>
  items.filter(({ exchange }) => exchange.bookedByLink && exchange.status === 'planned')

/**
 * The bookings made through my public link while the application is open (ADR 0164). A toast tells each one once,
 * when a reading of my agenda finds it. The bookings that existed when the application opened are already in the
 * calendar.
 */
export function NewBookings() {
  const { t } = useTranslation()
  const [period] = useState(() => {
    const today = startOfDay(new Date())
    return { first: today, end: addDays(today, DAYS_A_LINK_OFFERS) }
  })
  const { data } = useQuery({ ...calendarQuery(period), refetchInterval: READ_AGAIN_MS })
  const told = useRef<Set<string>>(null)

  useEffect(() => {
    if (!data) {
      return
    }
    const booked = bookedOf(data.items)
    if (told.current === null) {
      told.current = new Set(booked.map(({ exchange }) => exchange.id))
      return
    }
    for (const { exchange, interlocutor } of booked.filter(
      ({ exchange }) => !told.current?.has(exchange.id),
    )) {
      told.current.add(exchange.id)
      toast.info(
        t('exchanges.bookings.new', {
          channel: t(`exchanges.channels.${exchange.channel}`),
          interlocutor: interlocutor.name ?? '',
          moment: formatMoment(exchange.occurredAt),
        }),
      )
    }
  }, [data, t])

  return null
}
