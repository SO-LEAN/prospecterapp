import { Link } from '@tanstack/react-router'
import { CopyCheck } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription, AlertTitle } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { useDismissPossibleDuplicateOfContact } from '../mutations'
import { type Contact, fullNameOf } from '../queries'

type PossibleDuplicate = Contact['possibleDuplicates'][number]

/** The contacts of their organization they resemble, by their names: perhaps the same person, noted twice. */
export function PossibleDuplicatesOfContact({ contact }: { contact: Contact }) {
  const { t } = useTranslation()

  if (contact.possibleDuplicates.length === 0) {
    return null
  }

  return (
    <Alert role="note" className="border-warning bg-warning/15">
      <CopyCheck className="text-warning" aria-hidden />
      <AlertTitle>{t('contacts.possibleDuplicates.title')}</AlertTitle>
      <AlertDescription>
        <p>{t('contacts.possibleDuplicates.explanation')}</p>
        <ul className="grid gap-2">
          {contact.possibleDuplicates.map((duplicate) => (
            <PossibleDuplicateItem key={duplicate.id} contactId={contact.id} duplicate={duplicate} />
          ))}
        </ul>
      </AlertDescription>
    </Alert>
  )
}

function PossibleDuplicateItem({
  contactId,
  duplicate,
}: {
  contactId: string
  duplicate: PossibleDuplicate
}) {
  const { t } = useTranslation()
  const dismiss = useDismissPossibleDuplicateOfContact()
  const name = fullNameOf(duplicate)

  return (
    <li className="flex flex-wrap items-center gap-x-3 gap-y-1">
      <Link
        to="/contacts/$contactId"
        params={{ contactId: duplicate.id }}
        className="font-medium text-foreground underline-offset-4 hover:underline"
      >
        {name}
      </Link>
      {duplicate._links.dismiss && (
        <Button
          variant="outline"
          size="sm"
          disabled={dismiss.isPending}
          aria-label={t('contacts.possibleDuplicates.dismissOf', { name })}
          onClick={() => dismiss.mutate({ params: { path: { id: contactId, duplicateId: duplicate.id } } })}
        >
          {t('contacts.possibleDuplicates.dismiss')}
        </Button>
      )}
    </li>
  )
}
