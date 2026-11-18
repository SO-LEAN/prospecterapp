import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { CardPage } from '@/app/layout/card-page'
import { isProblem } from '@/shared/api/problem'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageSkeleton } from '@/shared/ui/page-skeleton'
import { publicBookingPageQuery } from '../queries'

type Props = { code: string }

/** The page a public link leads to, read without signing in: open, it says how its host meets the bookers (the slots come next). */
export function PublicBookingPage({ code }: Props) {
  const { t } = useTranslation()
  const page = useQuery({ ...publicBookingPageQuery(code), retry: false })

  if (page.isPending) {
    return <PageSkeleton />
  }
  if (page.isError) {
    return isProblem(page.error) && page.error.status === 404 ? (
      <CardPage title={t('booking.public.notFound')} description={t('booking.public.notFoundDescription')} />
    ) : (
      <ErrorState error={page.error} reset={() => void page.refetch()} />
    )
  }

  const channels = page.data.channels.map((channel) => t(`booking.channels.${channel}`)).join(', ')

  return (
    <CardPage
      title={t('booking.public.title')}
      description={page.data.open ? t('booking.public.offers', { channels }) : t('booking.public.noSlot')}
    />
  )
}
