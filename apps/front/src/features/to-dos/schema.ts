import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { DUE_SHORTCUTS } from './due'
import type { ToDo, ToDoInput } from './queries'

const MAX_TEXT = 500

/** The value that chooses the organization itself rather than one of its contacts. */
export const ITSELF = ''

/**
 * Mirrors the API's `ToDoInput`: a text, a day, and an hour on that day when one is given. On an organization's card,
 * the to-do is also for the organization itself or for one of its contacts.
 */
export const toDoSchema = z.object({
  text: text(MAX_TEXT).min(1, { error: () => i18n.t('validation.required') }),
  dueOn: z.string().min(1, { error: () => i18n.t('validation.required') }),
  dueAt: z.string(),
  forContact: z.string(),
})

export type ToDoValues = z.input<typeof toDoSchema>

export const toDoFields = ['text', 'dueOn', 'dueAt'] as const

/** Quick to log: due tomorrow, at no hour. Only the text remains to write. */
export const newToDo = (now: Date): ToDoValues => ({
  text: '',
  dueOn: DUE_SHORTCUTS.tomorrow(now),
  dueAt: '',
  forContact: ITSELF,
})

/** The to-do as it currently is, to revise it. */
export const revisionOf = (toDo: ToDo): ToDoValues => ({
  text: toDo.text,
  dueOn: toDo.dueOn,
  dueAt: toDo.dueAt ?? '',
  forContact: ITSELF,
})

export const toToDoInput = (values: ToDoValues): ToDoInput => ({
  text: values.text.trim(),
  dueOn: values.dueOn,
  dueAt: orNull(values.dueAt),
})

/** The contact the to-do is for, null for the organization itself. */
export const contactIdOf = (values: ToDoValues): string | null =>
  values.forContact === ITSELF ? null : values.forContact
