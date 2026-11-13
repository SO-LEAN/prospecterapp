import { useQuery } from '@tanstack/react-query'
import { EllipsisVertical, Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { memberName } from '@/shared/lib/member-name'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { formatDue, isOverdue, split } from '../due'
import {
  useEraseToDo,
  useNoteOrganizationToDo,
  useNoteToDo,
  useReviseToDo,
  useTickOffToDo,
  useUntickToDo,
} from '../mutations'
import {
  contactToDosQuery,
  organizationToDosQuery,
  type ToDo,
  type ToDoInput,
  type ToDoList,
} from '../queries'
import { revisionOf } from '../schema'
import { ToDoForm } from './to-do-form'

/** Told once a to-do is ticked off: the page may propose to note the exchange it led to. */
export type OnTickedOff = (toDo: ToDo) => void

type SectionProps = { onTickedOff?: OnTickedOff; className?: string }

/** The to-dos for the contact, noted on its card. */
export function ContactToDosSection({ contactId, ...props }: SectionProps & { contactId: string }) {
  const { data } = useQuery(contactToDosQuery(contactId))
  const note = useNoteToDo()

  return (
    data && (
      <ToDosCard
        list={data}
        onNote={(body) => note.mutateAsync({ params: { path: { id: contactId } }, body })}
        {...props}
      />
    )
  )
}

/** The to-dos kept for the organization, for it or for its contacts, each of these named. */
export function OrganizationToDosSection({
  organizationId,
  ...props
}: SectionProps & { organizationId: string }) {
  const { data } = useQuery(organizationToDosQuery(organizationId))
  const note = useNoteOrganizationToDo()

  return (
    data && (
      <ToDosCard
        list={data}
        withTheirContact
        onNote={(body) => note.mutateAsync({ params: { path: { id: organizationId } }, body })}
        {...props}
      />
    )
  )
}

type CardProps = SectionProps & {
  list: ToDoList
  onNote: (body: ToDoInput) => Promise<unknown>
  withTheirContact?: boolean
}

/** What is to do, the soonest due first, those overdue in red; those done folded beneath. */
export function ToDosCard({ list, onNote, onTickedOff, withTheirContact = false, className }: CardProps) {
  const { t } = useTranslation()
  const { toDo, done } = split(list.toDos)
  const now = new Date()

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{t('toDos.title')}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {list._links.noteToDo && <ToDoForm onSubmit={onNote} />}
        {toDo.length === 0 && <p className="text-muted-foreground">{t('toDos.none')}</p>}
        <ToDoLines
          label={t('toDos.title')}
          toDos={toDo}
          now={now}
          withTheirContact={withTheirContact}
          onTickedOff={onTickedOff}
        />
        {done.length > 0 && (
          <details>
            <summary className="text-muted-foreground">
              {t('toDos.doneCount', { count: done.length })}
            </summary>
            <ToDoLines
              label={t('toDos.done')}
              toDos={done}
              now={now}
              withTheirContact={withTheirContact}
              onTickedOff={onTickedOff}
            />
          </details>
        )}
      </CardContent>
    </Card>
  )
}

type LinesProps = {
  label: string
  toDos: ToDo[]
  now: Date
  withTheirContact: boolean
  onTickedOff?: OnTickedOff
}

function ToDoLines({ label, toDos, ...props }: LinesProps) {
  return (
    <ul className="divide-y" aria-label={label}>
      {toDos.map((toDo) => (
        <li key={toDo.id} className="py-1.5">
          <ToDoLine toDo={toDo} {...props} />
        </li>
      ))}
    </ul>
  )
}

type LineProps = Omit<LinesProps, 'label' | 'toDos'> & { toDo: ToDo }

/** Ticked off or unticked in one click, revised or erased from its menu, as its links allow. */
export function ToDoLine({ toDo, now, withTheirContact, onTickedOff }: LineProps) {
  const { t } = useTranslation()
  const tickOff = useTickOffToDo()
  const untick = useUntickToDo()
  const erase = useEraseToDo()
  const revise = useReviseToDo()
  const [revising, setRevising] = useState(false)
  const done = Boolean(toDo.doneAt)
  const toggle = done ? toDo._links.untick : toDo._links.tickOff
  const [clicked, setClicked] = useState<boolean | null>(null)
  const overdue = isOverdue(toDo, now)
  const contactName =
    toDo.contact && [toDo.contact.name.firstName, toDo.contact.name.lastName].filter(Boolean).join(' ')
  const params = { params: { path: { id: toDo.id } } }

  /** The box shows the click at once, until the lists read again tell the new state, or the refusal the former one. */
  const onToggle = () => {
    setClicked(!done)
    const shownAsItIs = { onSettled: () => setClicked(null) }

    if (done) {
      untick.mutate(params, shownAsItIs)
      return
    }
    tickOff.mutate(params, { ...shownAsItIs, onSuccess: () => onTickedOff?.(toDo) })
  }

  return (
    <div className="flex items-start gap-3">
      <input
        type="checkbox"
        className="mt-1 size-4 shrink-0 accent-primary"
        checked={clicked ?? done}
        disabled={!toggle || tickOff.isPending || untick.isPending}
        aria-label={t(done ? 'toDos.untick' : 'toDos.tickOff', { text: toDo.text })}
        onChange={onToggle}
      />
      <div className="min-w-0 flex-1">
        <p className={cn('break-words', done && 'text-muted-foreground line-through')}>{toDo.text}</p>
        <p className="flex flex-wrap gap-x-1 text-muted-foreground text-xs">
          <span className={cn(overdue && 'font-medium text-destructive')}>
            {overdue ? t('toDos.overdue', { due: formatDue(toDo) }) : formatDue(toDo)}
          </span>
          {withTheirContact && contactName && <span>{t('toDos.forContact', { name: contactName })}</span>}
          <span>{t('toDos.heldBy', { name: memberName(toDo.heldBy) })}</span>
        </p>
      </div>
      {(toDo._links.revise || toDo._links.erase) && (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" aria-label={t('toDos.menu', { text: toDo.text })}>
              <EllipsisVertical aria-hidden />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            {toDo._links.revise && (
              <DropdownMenuItem onSelect={() => setRevising(true)}>
                <Pencil aria-hidden /> {t('common.edit')}
              </DropdownMenuItem>
            )}
            {toDo._links.erase && (
              <DropdownMenuItem variant="destructive" onSelect={() => erase.mutate(params)}>
                <Trash2 aria-hidden /> {t('common.remove')}
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      )}
      <Dialog open={revising} onOpenChange={setRevising}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('toDos.revise')}</DialogTitle>
            <DialogDescription>{toDo.text}</DialogDescription>
          </DialogHeader>
          <ToDoForm
            defaultValues={revisionOf(toDo)}
            onCancel={() => setRevising(false)}
            onSubmit={async (body) => {
              await revise.mutateAsync({ ...params, body })
              setRevising(false)
            }}
          />
        </DialogContent>
      </Dialog>
    </div>
  )
}
