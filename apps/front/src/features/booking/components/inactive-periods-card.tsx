import { Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatCalendarDay } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { Field } from '@/shared/ui/form/field'
import { Input } from '@/shared/ui/input'
import { type BookingPage, useAddInactivePeriod, useRemoveInactivePeriod } from '../mutations'

type Props = { page: BookingPage }

/** My inactive periods that have not ended, each one removable alone, and the form that adds one. */
export function InactivePeriodsCard({ page }: Props) {
  const { t } = useTranslation()
  const remove = useRemoveInactivePeriod()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('booking.periods.title')}</CardTitle>
        <CardDescription>{t('booking.periods.description')}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {page.inactivePeriods.length === 0 ? (
          <p className="text-muted-foreground">{t('booking.periods.none')}</p>
        ) : (
          <ul className="space-y-2">
            {page.inactivePeriods.map((period) => {
              const days = {
                firstDay: formatCalendarDay(period.firstDay),
                lastDay: formatCalendarDay(period.lastDay),
              }
              return (
                <li key={period.id} className="flex items-center justify-between gap-2">
                  <span>{t('booking.periods.period', days)}</span>
                  <Button
                    variant="ghost"
                    size="icon"
                    aria-label={t('booking.periods.remove', days)}
                    disabled={remove.isPending}
                    onClick={() => remove.mutate({ params: { path: { id: period.id } } })}
                  >
                    <Trash2 aria-hidden />
                  </Button>
                </li>
              )
            })}
          </ul>
        )}
        {page._links.addInactivePeriod && <AddPeriod />}
      </CardContent>
    </Card>
  )
}

function AddPeriod() {
  const { t } = useTranslation()
  const add = useAddInactivePeriod()
  const [firstDay, setFirstDay] = useState('')
  const [lastDay, setLastDay] = useState('')

  const emptied = () => {
    setFirstDay('')
    setLastDay('')
  }

  const submit = (event: React.FormEvent) => {
    event.preventDefault()
    add.mutate({ body: { firstDay, lastDay } }, { onSuccess: emptied })
  }

  return (
    <form className="flex flex-wrap items-end gap-3" onSubmit={submit}>
      <Field label={t('booking.periods.firstDay')}>
        <Input type="date" required value={firstDay} onChange={(event) => setFirstDay(event.target.value)} />
      </Field>
      <Field label={t('booking.periods.lastDay')}>
        <Input type="date" required value={lastDay} onChange={(event) => setLastDay(event.target.value)} />
      </Field>
      <Button type="submit" variant="outline" disabled={add.isPending}>
        {t('booking.periods.add')}
      </Button>
    </form>
  )
}
