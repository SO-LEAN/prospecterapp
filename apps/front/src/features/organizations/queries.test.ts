import { describe, expect, it } from 'vitest'
import { contactsToNoteAToDoFor, type OrganizationContact } from './queries'

const link = (href: string, method = 'GET') => ({ href, method })

const aContact = (
  id: string,
  firstName: string,
  lastName: string,
  mayNoteAToDo: boolean,
): OrganizationContact => ({
  id,
  name: { firstName, lastName },
  title: 'x',
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: {
    contact: link(`/api/contacts/${id}`),
    ...(mayNoteAToDo && { noteToDo: link(`/api/contacts/${id}/to-dos`, 'POST') }),
  },
})

describe('contactsToNoteAToDoFor', () => {
  it('offers by their name only the contacts for whom a to-do can be noted', () => {
    expect(
      contactsToNoteAToDoFor([
        aContact('a', 'Nicolas', 'Bonnet', true),
        aContact('b', 'Lou', 'Durand', false),
      ]),
    ).toEqual([{ id: 'a', name: 'Nicolas Bonnet' }])
  })
})
