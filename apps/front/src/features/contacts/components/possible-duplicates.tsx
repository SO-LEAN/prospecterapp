import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { PossibleDuplicatesNote, possibleDuplicateLinkStyle } from '@/shared/ui/possible-duplicates-note'
import { useDismissPossibleDuplicateOfContact } from '../mutations'
import { type Contact, fullNameOf } from '../queries'

/** The contacts in their organization whose names resemble theirs. They may be the same person, logged twice. */
export function PossibleDuplicatesOfContact({ contact }: { contact: Contact }) {
  const { t } = useTranslation()
  const dismiss = useDismissPossibleDuplicateOfContact()

  return (
    <PossibleDuplicatesNote
      explanation={t('contacts.possibleDuplicates.explanation')}
      duplicates={contact.possibleDuplicates.map((duplicate) => {
        const name = fullNameOf(duplicate)
        return {
          id: duplicate.id,
          name,
          link: (
            <Link
              to="/contacts/$contactId"
              params={{ contactId: duplicate.id }}
              className={possibleDuplicateLinkStyle}
            >
              {name}
            </Link>
          ),
          dismiss: duplicate._links.dismiss && {
            pending: dismiss.isPending,
            onDismiss: () =>
              dismiss.mutate({ params: { path: { id: contact.id, duplicateId: duplicate.id } } }),
          },
        }
      })}
    />
  )
}
