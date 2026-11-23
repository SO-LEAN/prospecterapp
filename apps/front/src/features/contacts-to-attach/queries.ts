import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

/** A contact I met, waiting for me to attach them to an organization (ADR 0146). */
export type ContactToAttach = components['schemas']['ContactToAttachResource']
export type ToAttach = components['schemas']['ToAttachResource']

/** My account's organization whose email has the same domain as theirs. There is none for a public mailbox. */
export const proposedOrganizationQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/proposed-organization', { params: { path: { id: contactId } } })

/** My organizations whose name or email begins with the searched text. */
export const organizationsFoundQuery = (q: string) =>
  api.queryOptions('get', '/api/organizations', { params: { query: { q } } })

export const nameOf = (contact: Pick<ContactToAttach, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** « acme.fr » → « Acme »: the proposed name for the organization created for an email's domain. */
export function organizationNamedAfter(email: string): string {
  const domain = email.slice(email.lastIndexOf('@') + 1)
  const name = domain.split('.')[0] ?? ''
  return name.charAt(0).toUpperCase() + name.slice(1)
}

export const domainOf = (email: string) => email.slice(email.lastIndexOf('@') + 1)
