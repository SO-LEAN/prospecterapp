import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Contact = components['schemas']['ContactResource']
export type ContactInList = components['schemas']['ContactInListResource']

export type ContactSearch = { page: number; q: string }

export const contactsQuery = ({ page, q }: ContactSearch) =>
  api.queryOptions('get', '/api/contacts', { params: { query: { page, q } } })

export const contactQuery = (id: string) =>
  api.queryOptions('get', '/api/contacts/{id}', { params: { path: { id } } })

export const organizationsOfContactQuery = (id: string) =>
  api.queryOptions('get', '/api/contacts/{id}/organizations', { params: { path: { id } } })

export const fullNameOf = (contact: Pick<Contact, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')
