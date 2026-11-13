import { Video } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import type { Exchange } from '../queries'

type Props = { exchange: Pick<Exchange, 'status' | 'link'> }

/** A video call still to come is joined in one click, in a new tab; nothing without its link. */
export function JoinTheVideoCall({ exchange }: Props) {
  const { t } = useTranslation()

  if (exchange.status !== 'planned' || !exchange.link) {
    return null
  }

  return (
    <Button variant="outline" size="sm" asChild>
      <a href={exchange.link} target="_blank" rel="noreferrer">
        <Video aria-hidden /> {t('exchanges.join')}
      </a>
    </Button>
  )
}
