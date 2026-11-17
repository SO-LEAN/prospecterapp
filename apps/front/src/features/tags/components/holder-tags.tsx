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

/**
 * The tags of a contact, on their card: its own, then those it inherits, named after the organizations it works for,
 * given by the route, and the automatic ones.
 */
export function ContactTags({
  contactId,
  organizations,
}: {
  contactId: string
  organizations: { id: string; name: string }[]
}) {
  const { data } = useQuery(contactTagsQuery(contactId))
  const tag = useTagContact()
  const takeOff = useTakeOffContactTag()
  const organizationNamed = (organizationId: string) =>
    organizations.find((organization) => organization.id === organizationId)?.name

  return (
    data && (
      <TagsLine
        tags={namesOf(data)}
        inherited={data.inherited.map(({ name, from }) => ({
          name,
          from: from.map(organizationNamed).filter((organization) => organization !== undefined),
        }))}
        automatic={data.automatic}
        onTag={(typed) => tag.mutate({ params: { path: { id: contactId } }, body: { tags: [typed] } })}
        onTakeOff={(name) => takeOff.mutate({ params: { path: { id: contactId, tag: nameOf(name) } } })}
      />
    )
  )
}
