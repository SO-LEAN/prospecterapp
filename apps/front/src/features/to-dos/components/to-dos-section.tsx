import { useQuery } from '@tanstack/react-query'
import { CalendarClock, EllipsisVertical, Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { memberName } from '@/shared/lib/member-name'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { formatDue, isOverdue, split } from '../due'
import { useEraseToDo, useNoteOrganizationToDo, useNoteToDo, useReviseToDo } from '../mutations'
import {
  contactToDosQuery,
  organizationToDosQuery,
  type ToDo,
  type ToDoInput,
  type ToDoList,
} from '../queries'
import { revisionOf } from '../schema'
import { useToggle } from '../use-toggle'
import { ToDoForm } from './to-do-form'

/** Called once a to-do is ticked off. The page may then propose to log the exchange it led to. */
export type OnTickedOff = (toDo: ToDo) => void

type SectionProps = { onTickedOff?: OnTickedOff; className?: string }

/** The contact's to-dos, logged on its card. */
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

/** The to-dos kept for the organization: those for the organization itself and those for its contacts, each contact named. */
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

type AddProps = Pick<CardProps, 'onNote'> & { open: boolean; onOpenChange: (open: boolean) => void }

/**
 * Adds a new to-do in a dialog. The dialog closes once the to-do is added, or stays open for another one. It then shows
 * a new, empty form, due tomorrow, with its text focused. Emptying the same form in place would count on a render that
 * registers its fields again, and the React Compiler skips that render (`docs/architecture/front.md`, Forms).
 */
function AddToDoDialog({ onNote, open, onOpenChange }: AddProps) {
  const { t } = useTranslation()
  const [added, setAdded] = useState(0)

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{t('toDos.new')}</DialogTitle>
          <DialogDescription>{t('toDos.newDescription')}</DialogDescription>
        </DialogHeader>
        <ToDoForm
          key={added}
          submitLabel={t('toDos.add')}
          onCancel={() => onOpenChange(false)}
          onSubmit={async (body) => {
            await onNote(body)
            onOpenChange(false)
          }}
          onSubmitThenAnother={async (body) => {
            await onNote(body)
            setAdded((count) => count + 1)
          }}
        />
      </DialogContent>
    </Dialog>
  )
}

function AddButton({ onClick }: { onClick: () => void }) {
  const { t } = useTranslation()

  return (
    <Button size="sm" onClick={onClick}>
      <Plus aria-hidden /> {t('toDos.add')}
    </Button>
  )
}

/** What there is to do, the soonest due first and the overdue ones in red. The done ones are folded beneath. */
export function ToDosCard({ list, onNote, onTickedOff, withTheirContact = false, className }: CardProps) {
  const { t } = useTranslation()
  const { toDo, done } = split(list.toDos)
  const now = new Date()
  const [adding, setAdding] = useState(false)
  const mayAdd = Boolean(list._links.noteToDo)
  const nothingToDo = toDo.length === 0
  const add = () => setAdding(true)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="text-base">{t('toDos.title')}</CardTitle>
        {mayAdd && !nothingToDo && (
          <CardAction>
            <AddButton onClick={add} />
          </CardAction>
        )}
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {nothingToDo && (
          <div className="flex flex-col items-center gap-3 py-4 text-center">
            <p className="text-muted-foreground">{t('toDos.none')}</p>
            {mayAdd && <AddButton onClick={add} />}
          </div>
        )}
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
        {mayAdd && <AddToDoDialog onNote={onNote} open={adding} onOpenChange={setAdding} />}
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

type LineProps = Omit<LinesProps, 'label' | 'toDos'> & {
  toDo: ToDo
  /** Offers « Reporter », which revises its due day. */
  postponable?: boolean
  /** Its toast offers to take the tick back. */
  undoable?: boolean
}

/**
 * One click ticks it off or unticks it. Its menu revises or erases it. « Reporter », where it is offered, opens the same
 * revision. Its links decide which gestures it allows.
 */
export function ToDoLine({ toDo, now, withTheirContact, onTickedOff, postponable, undoable }: LineProps) {
  const { t } = useTranslation()
  const toggle = useToggle(toDo, { onTickedOff, undoable })
  const erase = useEraseToDo()
  const revise = useReviseToDo()
  const [revising, setRevising] = useState(false)
  const done = Boolean(toDo.doneAt)
  const overdue = isOverdue(toDo, now)
  const contactName =
    toDo.contact && [toDo.contact.name.firstName, toDo.contact.name.lastName].filter(Boolean).join(' ')
  const params = { params: { path: { id: toDo.id } } }

  return (
    <div className="flex items-start gap-3">
      <input type="checkbox" className="mt-1 size-4 shrink-0 accent-primary" {...toggle} />
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
      {postponable && toDo._links.revise && (
        <Button variant="outline" size="sm" onClick={() => setRevising(true)}>
          <CalendarClock aria-hidden /> {t('toDos.postpone')}
        </Button>
      )}
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
            submitLabel={t('common.save')}
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
