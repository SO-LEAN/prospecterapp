import { useQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { FullPageMessage } from '@/shared/ui/feedback/full-page-message'
import { nextReadingOfMyProfile } from '../me'
import { myAccountBeingPreparedQuery } from '../queries'

type Props = { children: ReactNode }

/**
 * What a signed-in member sees while their profile is in preparation. They wait while the screen reads their account
 * again until it exists. After too long, the screen tells them to try again later. The children are the offered
 * actions (signing out).
 */
export function MyProfileBeingPrepared({ children }: Props) {
  const { t } = useTranslation()
  const { errorUpdateCount } = useQuery(myAccountBeingPreparedQuery())

  if (nextReadingOfMyProfile(errorUpdateCount) === false) {
    return (
      <FullPageMessage
        title={t('myProfileBeingPrepared.notReady')}
        description={t('myProfileBeingPrepared.tryAgainLater')}
      >
        {children}
      </FullPageMessage>
    )
  }

  return (
    <FullPageMessage
      title={t('myProfileBeingPrepared.title')}
      description={t('myProfileBeingPrepared.description')}
      loading
    >
      {children}
    </FullPageMessage>
  )
}
