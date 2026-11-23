import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type ToDo = components['schemas']['ToDoResource']
export type ToDoList = components['schemas']['ToDoListResource']
export type ToDoInput = components['schemas']['ToDoInput']

/** A contact's to-dos, read in full. A contact has only a handful (ADR 0113). */
export const contactToDosQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/to-dos', { params: { path: { id: contactId } } })

/** The to-dos kept for an organization: those for the organization itself and those for its contacts. */
export const organizationToDosQuery = (organizationId: string) =>
  api.queryOptions('get', '/api/organizations/{id}/to-dos', { params: { path: { id: organizationId } } })

/** A to-do logged, changed or erased on one card shows on the others: its contact's card, its organizations' cards and mine. */
export const toDoKeys = {
  ofContacts: ['get', '/api/contacts/{id}/to-dos'],
  ofOrganizations: ['get', '/api/organizations/{id}/to-dos'],
  mine: ['get', '/api/me/to-dos'],
} as const
