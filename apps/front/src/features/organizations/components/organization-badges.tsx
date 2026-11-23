import { Badge } from '@/shared/ui/badge'
import { CreatedByAnAIBadge } from '@/shared/ui/created-by-an-ai-badge'
import type { Organization } from '../queries'

/** Under an organization's name: its legal form, its type, and « MCP » when an AI started prospecting it. */
export function OrganizationBadges({
  organization,
}: {
  organization: Pick<Organization, 'legalIdentity' | 'createdByAnAI'>
}) {
  const { form, type } = organization.legalIdentity

  return (
    <span className="flex flex-wrap gap-2">
      {form && <Badge variant="secondary">{form}</Badge>}
      {type && <Badge variant="outline">{type}</Badge>}
      {organization.createdByAnAI && <CreatedByAnAIBadge />}
    </span>
  )
}
