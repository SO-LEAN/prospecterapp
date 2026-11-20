import { Link } from '@tanstack/react-router'
import { CopyCheck } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription, AlertTitle } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { useDismissPossibleDuplicate } from '../mutations'
import type { Organization } from '../queries'

type PossibleDuplicate = Organization['possibleDuplicates'][number]

/** The organizations of the account it resembles, by its name and its city: perhaps the same, entered twice. */
export function PossibleDuplicates({ organization }: { organization: Organization }) {
  const { t } = useTranslation()

  if (organization.possibleDuplicates.length === 0) {
    return null
  }

  return (
    <Alert role="note" className="border-warning bg-warning/15">
      <CopyCheck className="text-warning" aria-hidden />
      <AlertTitle>{t('organizations.possibleDuplicates.title')}</AlertTitle>
      <AlertDescription>
        <p>{t('organizations.possibleDuplicates.explanation')}</p>
        <ul className="grid gap-2">
          {organization.possibleDuplicates.map((duplicate) => (
            <PossibleDuplicateItem
              key={duplicate.id}
              organizationId={organization.id}
              duplicate={duplicate}
            />
          ))}
        </ul>
      </AlertDescription>
    </Alert>
  )
}

function PossibleDuplicateItem({
  organizationId,
  duplicate,
}: {
  organizationId: string
  duplicate: PossibleDuplicate
}) {
  const { t } = useTranslation()
  const dismiss = useDismissPossibleDuplicate()

  return (
    <li className="flex flex-wrap items-center gap-x-3 gap-y-1">
      <Link
        to="/organizations/$organizationId"
        params={{ organizationId: duplicate.id }}
        className="font-medium text-foreground underline-offset-4 hover:underline"
      >
        {duplicate.name}
      </Link>
      {duplicate._links.dismiss && (
        <Button
          variant="outline"
          size="sm"
          disabled={dismiss.isPending}
          aria-label={t('organizations.possibleDuplicates.dismissOf', { name: duplicate.name })}
          onClick={() =>
            dismiss.mutate({ params: { path: { id: organizationId, duplicateId: duplicate.id } } })
          }
        >
          {t('organizations.possibleDuplicates.dismiss')}
        </Button>
      )}
    </li>
  )
}
