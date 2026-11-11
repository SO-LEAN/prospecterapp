import { useQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { FullPageMessage } from '@/shared/ui/feedback/full-page-message'
import { nextReadingOfMyProfile } from '../me'
import { myAccountBeingPreparedQuery } from '../queries'

type Props = { children: ReactNode }

/**
 * What a member signed in sees while their profile is being prepared: they wait, their account read again until it
 * exists, then are told to try again later; the actions offered (signing out) are the children.
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
