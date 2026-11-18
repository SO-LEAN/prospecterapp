import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

/** A contact waiting for me, who met them, to attach them to an organization (ADR 0146). */
export type ContactToAttach = components['schemas']['ContactToAttachResource']
export type ToAttach = components['schemas']['ToAttachResource']

/** The organization of my account whose email has the domain of theirs, none for a public mailbox. */
export const proposedOrganizationQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/proposed-organization', { params: { path: { id: contactId } } })

/** My organizations whose name or email begins so. */
export const organizationsFoundQuery = (q: string) =>
  api.queryOptions('get', '/api/organizations', { params: { query: { q } } })

export const nameOf = (contact: Pick<ContactToAttach, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** « acme.fr » → « Acme »: the name proposed for the organization created for the domain of an email. */
export function organizationNamedAfter(email: string): string {
  const domain = email.slice(email.lastIndexOf('@') + 1)
  const name = domain.split('.')[0] ?? ''
  return name.charAt(0).toUpperCase() + name.slice(1)
}

export const domainOf = (email: string) => email.slice(email.lastIndexOf('@') + 1)
