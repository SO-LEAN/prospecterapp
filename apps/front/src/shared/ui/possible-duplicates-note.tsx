import { CopyCheck } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Alert, AlertDescription, AlertTitle } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'

/** What the reader may be told of a possible duplicate: a link to it, and, when they may, how to say it is none. */
export type PossibleDuplicateShown = {
  id: string
  name: string
  link: ReactNode
  dismiss?: { pending: boolean; onDismiss: () => void }
}

/**
 * The mark « Doublon possible »: what the holder resembles, each with a link to it, and « Ce n'est pas un doublon »
 * when the reader may say it (ADR 0162, 0170).
 */
export function PossibleDuplicatesNote({
  explanation,
  duplicates,
}: {
  explanation: string
  duplicates: PossibleDuplicateShown[]
}) {
  const { t } = useTranslation()

  if (duplicates.length === 0) {
    return null
  }

  return (
    <Alert role="note" className="border-warning bg-warning/15">
      <CopyCheck className="text-warning" aria-hidden />
      <AlertTitle>{t('common.possibleDuplicates.title')}</AlertTitle>
      <AlertDescription>
        <p>{explanation}</p>
        <ul className="grid gap-2">
          {duplicates.map((duplicate) => (
            <li key={duplicate.id} className="flex flex-wrap items-center gap-x-3 gap-y-1">
              {duplicate.link}
              {duplicate.dismiss && (
                <Button
                  variant="outline"
                  size="sm"
                  disabled={duplicate.dismiss.pending}
                  aria-label={t('common.possibleDuplicates.dismissOf', { name: duplicate.name })}
                  onClick={duplicate.dismiss.onDismiss}
                >
                  {t('common.possibleDuplicates.dismiss')}
                </Button>
              )}
            </li>
          ))}
        </ul>
      </AlertDescription>
    </Alert>
  )
}

/** The link to a possible duplicate, styled alike wherever it is named. */
export const possibleDuplicateLinkStyle = 'font-medium text-foreground underline-offset-4 hover:underline'
