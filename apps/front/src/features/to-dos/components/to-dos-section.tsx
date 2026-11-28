import { useQuery } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { split } from '../due'
import { useNoteOrganizationToDo, useNoteToDo } from '../mutations'
import {
  contactToDosQuery,
  organizationToDosQuery,
  type ToDo,
  type ToDoInput,
  type ToDoList,
} from '../queries'
import { type Recipients, ToDoForm } from './to-do-form'
import { ToDoLine } from './to-do-line'

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

/**
 * The to-dos kept for the organization: those for the organization itself and those for its contacts, each contact
 * named. A new one is noted for the organization, or for the contact chosen among the recipients.
 */
export function OrganizationToDosSection({
  organizationId,
  ...props
}: SectionProps & { organizationId: string; recipients?: Recipients }) {
  const { data } = useQuery(organizationToDosQuery(organizationId))
  const noteForTheOrganization = useNoteOrganizationToDo()
  const noteForTheContact = useNoteToDo()

  const note = (body: ToDoInput, contactId: string | null) =>
    contactId
      ? noteForTheContact.mutateAsync({ params: { path: { id: contactId } }, body })
      : noteForTheOrganization.mutateAsync({ params: { path: { id: organizationId } }, body })

  return data && <ToDosCard list={data} withTheirContact onNote={note} {...props} />
}

type CardProps = SectionProps & {
  list: ToDoList
  /** Gets the to-do and the contact chosen among the recipients. The contact is null for the organization itself, or without recipients. */
  onNote: (body: ToDoInput, contactId: string | null) => Promise<unknown>
  withTheirContact?: boolean
  recipients?: Recipients
}

type AddProps = Pick<CardProps, 'onNote' | 'recipients'> & {
  open: boolean
  onOpenChange: (open: boolean) => void
}

/**
 * Adds a new to-do in a dialog. The dialog closes once the to-do is added, or stays open for another one. It then shows
 * a new, empty form, due tomorrow, with its text focused. Emptying the same form in place would count on a render that
 * registers its fields again, and the React Compiler skips that render (`docs/architecture/front.md`, Forms).
 */
function AddToDoDialog({ onNote, recipients, open, onOpenChange }: AddProps) {
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
          recipients={recipients}
          onCancel={() => onOpenChange(false)}
          onSubmit={async (body, contactId) => {
            await onNote(body, contactId)
            onOpenChange(false)
          }}
          onSubmitThenAnother={async (body, contactId) => {
            await onNote(body, contactId)
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
export function ToDosCard({
  list,
  onNote,
  onTickedOff,
  withTheirContact = false,
  recipients,
  className,
}: CardProps) {
  const { t } = useTranslation()
  const { toDo, done } = split(list.toDos)
  const [adding, setAdding] = useState(false)
  const mayAdd = Boolean(list._links.noteToDo)
  const nothingToDo = toDo.length === 0
  const add = () => setAdding(true)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{t('toDos.title')}</CardTitle>
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
              withTheirContact={withTheirContact}
              onTickedOff={onTickedOff}
            />
          </details>
        )}
        {mayAdd && (
          <AddToDoDialog onNote={onNote} recipients={recipients} open={adding} onOpenChange={setAdding} />
        )}
      </CardContent>
    </Card>
  )
}

type LinesProps = {
  label: string
  toDos: ToDo[]
  withTheirContact: boolean
  onTickedOff?: OnTickedOff
}

/** Shows each to-do on the line every list shares. The card names who holds it, and its contact on an organization's card. */
function ToDoLines({ label, toDos, withTheirContact, onTickedOff }: LinesProps) {
  return (
    <ul className="space-y-2" aria-label={label}>
      {toDos.map((toDo) => (
        <li key={toDo.id}>
          <ToDoLine
            toDo={toDo}
            withItsHolder
            withoutItsContact={!withTheirContact}
            onTickedOff={() => onTickedOff?.(toDo)}
          />
        </li>
      ))}
    </ul>
  )
}
