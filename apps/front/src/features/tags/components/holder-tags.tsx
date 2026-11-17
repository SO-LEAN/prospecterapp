import { useQuery } from '@tanstack/react-query'
import {
  useTagContact,
  useTagOrganization,
  useTakeOffContactTag,
  useTakeOffOrganizationTag,
} from '../mutations'
import { contactTagsQuery, organizationTagsQuery, type Tags } from '../queries'
import { TagsLine } from './tags-line'

const MARK = '#'

/** A tag is taken off by its name without its mark, as its route names it. */
const nameOf = (tag: string) => (tag.startsWith(MARK) ? tag.slice(MARK.length) : tag)

const namesOf = (tags: Tags) => tags.tags.map((tag) => tag.name)

/** The tags of an organization, on its card. */
export function OrganizationTags({ organizationId }: { organizationId: string }) {
  const { data } = useQuery(organizationTagsQuery(organizationId))
  const tag = useTagOrganization()
  const takeOff = useTakeOffOrganizationTag()

  return (
    data && (
      <TagsLine
        tags={namesOf(data)}
        onTag={(typed) => tag.mutate({ params: { path: { id: organizationId } }, body: { tags: [typed] } })}
        onTakeOff={(name) => takeOff.mutate({ params: { path: { id: organizationId, tag: nameOf(name) } } })}
      />
    )
  )
}

/** The tags of a contact, on their card. */
export function ContactTags({ contactId }: { contactId: string }) {
  const { data } = useQuery(contactTagsQuery(contactId))
  const tag = useTagContact()
  const takeOff = useTakeOffContactTag()

  return (
    data && (
      <TagsLine
        tags={namesOf(data)}
        onTag={(typed) => tag.mutate({ params: { path: { id: contactId } }, body: { tags: [typed] } })}
        onTakeOff={(name) => takeOff.mutate({ params: { path: { id: contactId, tag: nameOf(name) } } })}
      />
    )
  )
}
