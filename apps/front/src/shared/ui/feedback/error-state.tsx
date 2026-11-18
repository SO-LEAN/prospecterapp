import { AlertTriangle, Lock } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { isProblem, messageOf, type Problem } from '@/shared/api/problem'
import { Button } from '@/shared/ui/button'
import { EmptyState } from './empty-state'

type Props = { error: unknown; reset?: () => void }

const isForbidden = (error: unknown): error is Problem => isProblem(error) && error.status === 403

/** Error boundary content for routes: problem details from the API or a network failure. */
export function ErrorState({ error, reset }: Props) {
  return isForbidden(error) ? <Forbidden problem={error} /> : <Failure error={error} reset={reset} />
}

/** A role or a right I lack: told in my words, the right named when the API names it, nothing to try again. */
function Forbidden({ problem }: { problem: Problem }) {
  const { t } = useTranslation()

  return (
    <EmptyState
      icon={Lock}
      title={t('errors.forbidden')}
      description={problem.reason ? messageOf(problem) : undefined}
    />
  )
}

function Failure({ error, reset }: Props) {
  const { t } = useTranslation()

  return (
    <EmptyState
      icon={AlertTriangle}
      title={t('errors.title')}
      description={isProblem(error) ? messageOf(error) : t('errors.network')}
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
