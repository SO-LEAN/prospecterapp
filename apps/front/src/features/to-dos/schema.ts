import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { DUE_SHORTCUTS } from './due'
import type { ToDo, ToDoInput } from './queries'

const MAX_TEXT = 500

/** Mirrors `ToDoInput` of the API: a text, a day, an hour of it when said. */
export const toDoSchema = z.object({
  text: text(MAX_TEXT).min(1, { error: () => i18n.t('validation.required') }),
  dueOn: z.string().min(1, { error: () => i18n.t('validation.required') }),
  dueAt: z.string(),
})

export type ToDoValues = z.input<typeof toDoSchema>

export const toDoFields = ['text', 'dueOn', 'dueAt'] as const

/** Quick to note: due tomorrow, at no hour; only the text is to write. */
export const newToDo = (now: Date): ToDoValues => ({
  text: '',
  dueOn: DUE_SHORTCUTS.tomorrow(now),
  dueAt: '',
})

/** The to-do as it is, to revise it. */
export const revisionOf = (toDo: ToDo): ToDoValues => ({
  text: toDo.text,
  dueOn: toDo.dueOn,
  dueAt: toDo.dueAt ?? '',
})

export const toToDoInput = (values: ToDoValues): ToDoInput => ({
  text: values.text.trim(),
  dueOn: values.dueOn,
  dueAt: orNull(values.dueAt),
})
