import { AlertTriangle, Lock } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { isProblem, messageOf, type Problem } from '@/shared/api/problem'
import type { fr } from '@/shared/i18n/fr'
import { Button } from '@/shared/ui/button'
import { EmptyState } from './empty-state'

type Props = { error: unknown; reset?: () => void }

const isForbidden = (error: unknown): error is Problem => isProblem(error) && error.status === 403

/** The reason of a refusal by role alone, which names no right. The page's title already says it. */
const ROLE_NOT_ALLOWED: keyof typeof fr.refusals = 'roleNotAllowed'

const namesTheRightLacked = (problem: Problem) =>
  Boolean(problem.reason) && problem.reason !== ROLE_NOT_ALLOWED

/** The routes' error boundary content: the API's problem details or a network failure. */
export function ErrorState({ error, reset }: Props) {
  return isForbidden(error) ? <Forbidden problem={error} /> : <Failure error={error} reset={reset} />
}

/** A role or a right I lack, in my words. It names the right when the API names it, and offers no retry. */
function Forbidden({ problem }: { problem: Problem }) {
  const { t } = useTranslation()

  return (
    <EmptyState
      icon={Lock}
      title={t('errors.forbidden')}
      description={namesTheRightLacked(problem) ? messageOf(problem) : undefined}
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
