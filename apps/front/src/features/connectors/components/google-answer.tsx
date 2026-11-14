import { useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { isProblem, messageOf } from '@/shared/api/problem'
import { PageSkeleton } from '@/shared/ui/page-skeleton'
import { useGiveGoogleItsAnswer } from '../mutations'

export type GoogleAnswer = { code?: string; state?: string; error?: string }

type Props = {
  answer: GoogleAnswer
  onDone: () => void
}

/**
 * Where Google brings the member back: the code it gave goes to the API, once, then the member sees how it went on
 * the page of the connectors. A refusal at Google (`error=access_denied`) connects nothing.
 */
export function GoogleAnswerPage({ answer, onDone }: Props) {
  const { t } = useTranslation()
  const giveTheAnswer = useGiveGoogleItsAnswer()
  const given = useRef(false)

  useEffect(() => {
    if (given.current) {
      return
    }
    given.current = true
    const { code, state } = answer
    if (!code || !state) {
      toast.error(t('settings.connectors.answer.refused'))
      onDone()
      return
    }
    giveTheAnswer.mutate(
      { body: { code, state } },
      {
        onSuccess: () => toast.success(t('settings.connectors.answer.connected')),
        onError: (error) =>
          toast.error(isProblem(error) ? messageOf(error) : t('settings.connectors.answer.failed')),
        onSettled: onDone,
      },
    )
  }, [answer, giveTheAnswer, onDone, t])

  return <PageSkeleton />
}
