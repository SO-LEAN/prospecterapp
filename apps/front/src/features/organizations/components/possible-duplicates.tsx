import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { PossibleDuplicatesNote, possibleDuplicateLinkStyle } from '@/shared/ui/possible-duplicates-note'
import { useDismissPossibleDuplicate } from '../mutations'
import type { Organization } from '../queries'

/** The account's organizations that it resembles by name and city. They may be the same organization, entered twice. */
export function PossibleDuplicates({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const dismiss = useDismissPossibleDuplicate()

  return (
    <PossibleDuplicatesNote
      explanation={t('organizations.possibleDuplicates.explanation')}
      duplicates={organization.possibleDuplicates.map((duplicate) => ({
        id: duplicate.id,
        name: duplicate.name,
        link: (
          <Link
            to="/organizations/$organizationId"
            params={{ organizationId: duplicate.id }}
            className={possibleDuplicateLinkStyle}
          >
            {duplicate.name}
          </Link>
        ),
        dismiss: duplicate._links.dismiss && {
          pending: dismiss.isPending,
          onDismiss: () =>
            dismiss.mutate({ params: { path: { id: organization.id, duplicateId: duplicate.id } } }),
        },
      }))}
    />
  )
}
