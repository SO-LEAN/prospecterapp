import { zodResolver } from '@hookform/resolvers/zod'
import { CalendarClock, CalendarX2, CircleCheck, PhoneMissed } from 'lucide-react'
import { useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
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
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import { useCancelExchange, useMarkExchangeMissed, useUndoable } from '../mutations'
import { dateAndTimeOf, type MoveValues, moveFields, moveSchema, SHORTCUTS, toMoment } from '../planning'
import type { Canceller, ExchangeDetail, Moment, Report } from '../queries'
import { localMoment, type ReportValues, reportFields, reportOf, reportSchema, toReport } from '../schema'
import { type ChosenFile, FilesField } from './exchange-form'

type ActionsProps = { exchange: ExchangeDetail; onReport: () => void; onMove: () => void }

/**
 * What its holder does of a planned exchange, as its links offer it: done or missed once its time has come, moved,
 * cancelled; missed and cancelled are carried out once their toast no longer lets them be taken back.
 */
export function PlannedExchangeActions({ exchange, onReport, onMove }: ActionsProps) {
  const { t } = useTranslation()
  const markMissed = useMarkExchangeMissed()
  const undoable = useUndoable()
  const params = { path: { id: exchange.id } }

  return (
    <>
      {exchange._links.markDone && (
        <Button onClick={onReport}>
          <CircleCheck aria-hidden /> {t('exchanges.handling.done')}
        </Button>
      )}
      {exchange._links.markMissed && (
        <Button
          variant="outline"
          onClick={() => undoable(t('exchanges.handling.markedMissed'), () => markMissed.mutate({ params }))}
        >
          <PhoneMissed aria-hidden /> {t('exchanges.handling.missed')}
        </Button>
      )}
      {exchange._links.move && (
        <Button variant="outline" onClick={onMove}>
          <CalendarClock aria-hidden /> {t('exchanges.handling.move')}
        </Button>
      )}
      {exchange._links.cancel && <CancelExchange exchangeId={exchange.id} />}
    </>
  )
}

/** Who called it off, when the prospector wishes to say it. */
const CANCELLERS: readonly Canceller[] = ['prospector', 'contact']
const NOT_SAID = ''
const CANCELLER_CHOICES: readonly (Canceller | typeof NOT_SAID)[] = [NOT_SAID, ...CANCELLERS]

/** Cancelled once confirmed, saying who called it off if wished: it stays struck through in the timeline. */
function CancelExchange({ exchangeId }: { exchangeId: string }) {
  const { t } = useTranslation()
  const cancel = useCancelExchange()
  const undoable = useUndoable()
  const [open, setOpen] = useState(false)
  const [canceller, setCanceller] = useState<Canceller | typeof NOT_SAID>(NOT_SAID)

  const confirm = () => {
    setOpen(false)
    undoable(t('exchanges.handling.cancelled'), () =>
      cancel.mutate({ params: { path: { id: exchangeId } }, body: { canceller: canceller || null } }),
    )
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <CalendarX2 aria-hidden /> {t('exchanges.handling.cancel')}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('exchanges.handling.cancelTitle')}</DialogTitle>
          <DialogDescription>{t('exchanges.handling.cancelDescription')}</DialogDescription>
        </DialogHeader>
        <fieldset className="space-y-2 text-sm">
          <legend className="font-medium">{t('exchanges.handling.canceller')}</legend>
          {CANCELLER_CHOICES.map((value) => (
            <label key={value} className="flex items-center gap-2">
              <input
                type="radio"
                name="canceller"
                className="size-4 accent-primary"
                checked={canceller === value}
                onChange={() => setCanceller(value)}
              />
              {value === NOT_SAID
                ? t('exchanges.handling.notSaid')
                : t(`exchanges.handling.cancellers.${value}`)}
            </label>
          ))}
        </fieldset>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('exchanges.handling.keep')}</Button>
          </DialogClose>
          <Button variant="destructive" onClick={confirm}>
            {t('exchanges.handling.cancelConfirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

type ReportProps = {
  exchange: ExchangeDetail
  onSubmit: (report: Report, files: File[]) => Promise<unknown>
  onCancel: () => void
}

/** Its report, written from its agenda, the moment it really took place, and the files it brought. */
export function ReportForm({ exchange, onSubmit, onCancel }: ReportProps) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const [files, setFiles] = useState<ChosenFile[]>([])
  const form = useForm<ReportValues>({
    resolver: zodResolver(reportSchema),
    defaultValues: reportOf(exchange),
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(
        toReport(values),
        files.map(({ file }) => file),
      )
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, reportFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      <h3 className="font-medium">{t('exchanges.handling.reportTitle')}</h3>
      <Errors errors={globalErrors} />
      <FormField form={form} name="occurredAt" label={t('exchanges.occurredAt')}>
        <Input type="datetime-local" max={localMoment(new Date())} {...form.register('occurredAt')} />
      </FormField>
      <FormField
        form={form}
        name="text"
        label={t('exchanges.handling.report')}
        optional={t('common.optional')}
      >
        <Textarea rows={5} {...form.register('text')} />
      </FormField>
      <FilesField files={files} onChange={setFiles} />
      <Actions onCancel={onCancel} submitting={isSubmitting} />
    </form>
  )
}

type MoveProps = {
  exchange: ExchangeDetail
  onSubmit: (moment: Moment) => Promise<unknown>
  onCancel: () => void
}

/** Another moment to come, from its own or a shortcut; its duration stays. */
export function MoveForm({ exchange, onSubmit, onCancel }: MoveProps) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<MoveValues>({
    resolver: zodResolver(moveSchema),
    defaultValues: dateAndTimeOf(new Date(exchange.occurredAt)),
  })
  const { isSubmitting } = useFormState({ control: form.control })
  const setMoment = (moment: Date) => {
    const { date, time } = dateAndTimeOf(moment)
    form.setValue('date', date, { shouldValidate: true })
    form.setValue('time', time, { shouldValidate: true })
  }

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toMoment(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, moveFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      <h3 className="font-medium">{t('exchanges.handling.moveTitle')}</h3>
      <Errors errors={globalErrors} />
      <div className="flex flex-wrap gap-2">
        {(Object.keys(SHORTCUTS) as (keyof typeof SHORTCUTS)[]).map((shortcut) => (
          <Button
            key={shortcut}
            type="button"
            size="sm"
            variant="outline"
            onClick={() => setMoment(SHORTCUTS[shortcut](new Date()))}
          >
            {t(`exchanges.${shortcut}`)}
          </Button>
        ))}
      </div>
      <div className="grid grid-cols-2 gap-3">
        <FormField form={form} name="date" label={t('exchanges.date')}>
          <Input type="date" {...form.register('date')} />
        </FormField>
        <FormField form={form} name="time" label={t('exchanges.time')}>
          <Input type="time" step={900} {...form.register('time')} />
        </FormField>
      </div>
      <p className="text-muted-foreground text-xs">{t('exchanges.handling.durationKept')}</p>
      <Actions onCancel={onCancel} submitting={isSubmitting} />
    </form>
  )
}

function Errors({ errors }: { errors: string[] }) {
  return errors.length === 0 ? null : (
    <Alert variant="destructive">
      <AlertDescription>{errors.join(' ')}</AlertDescription>
    </Alert>
  )
}

function Actions({ onCancel, submitting }: { onCancel: () => void; submitting: boolean }) {
  const { t } = useTranslation()

  return (
    <div className="flex justify-end gap-2">
      <Button type="button" variant="ghost" onClick={onCancel}>
        {t('common.cancel')}
      </Button>
      <Button type="submit" disabled={submitting}>
        {submitting ? t('common.saving') : t('common.save')}
      </Button>
    </div>
  )
}
