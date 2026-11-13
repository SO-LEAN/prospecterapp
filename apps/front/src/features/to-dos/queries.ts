import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type ToDo = components['schemas']['ToDoResource']
export type ToDoList = components['schemas']['ToDoListResource']
export type ToDoInput = components['schemas']['ToDoInput']

/** The to-dos for a contact, read whole: a handful (ADR 0113). */
export const contactToDosQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/to-dos', { params: { path: { id: contactId } } })

/** The to-dos kept for an organization, for it or for its contacts. */
export const organizationToDosQuery = (organizationId: string) =>
  api.queryOptions('get', '/api/organizations/{id}/to-dos', { params: { path: { id: organizationId } } })

/** A to-do noted, changed or erased on one card shows on the others: of its contact, of its organizations, mine. */
export const toDoKeys = {
  ofContacts: ['get', '/api/contacts/{id}/to-dos'],
  ofOrganizations: ['get', '/api/organizations/{id}/to-dos'],
  mine: ['get', '/api/me/to-dos'],
} as const
