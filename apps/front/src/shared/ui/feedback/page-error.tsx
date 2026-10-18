import { useRouter } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { isProblem, messageOf } from '@/shared/api/problem'
import { Button } from '@/shared/ui/button'
import { FullPageMessage } from './full-page-message'

type Props = { error: unknown }

/** What a page shows when it could not load at all, outside the app shell: the api unreachable, most often. */
export function PageError({ error }: Props) {
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
    </FullPageMessage>
  )
}
