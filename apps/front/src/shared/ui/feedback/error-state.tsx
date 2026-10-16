import { AlertTriangle } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { isProblem } from '@/shared/api/problem'
import { Button } from '@/shared/ui/button'
import { EmptyState } from './empty-state'

type Props = { error: unknown; reset?: () => void }

/** Error boundary content for routes: problem details from the API or a network failure. */
export function ErrorState({ error, reset }: Props) {
  const { t } = useTranslation()

  return (
    <EmptyState
      icon={AlertTriangle}
      title={t('errors.title')}
      description={isProblem(error) ? error.detail || error.title : t('errors.network')}
      action={
        reset && (
          <Button variant="outline" onClick={reset}>
            {t('common.retry')}
          </Button>
        )
      }
    />
  )
}
