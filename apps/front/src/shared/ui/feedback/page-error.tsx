import { useRouter } from '@tanstack/react-router'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { isProblem, messageOf } from '@/shared/api/problem'
import { Button } from '@/shared/ui/button'
import { FullPageMessage } from './full-page-message'

type Props = { error: unknown; children?: ReactNode }

/**
 * What a page shows outside the app shell when it could not load at all, most often because the API is unreachable.
 * The children are the actions offered beside a retry, such as signing out for a signed-in member.
 */
export function PageError({ error, children }: Props) {
  const { t } = useTranslation()
  const router = useRouter()

  return (
    <FullPageMessage
      title={t('errors.title')}
      description={isProblem(error) ? messageOf(error) : t('errors.network')}
    >
      <Button variant="outline" onClick={() => router.invalidate()}>
        {t('common.retry')}
      </Button>
      {children}
    </FullPageMessage>
  )
}
