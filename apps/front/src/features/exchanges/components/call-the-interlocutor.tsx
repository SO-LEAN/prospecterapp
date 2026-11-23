import { useQuery } from '@tanstack/react-query'
import { Phone } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { telUrl } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { exchangeQuery } from '../queries'

/** The interlocutor's number, as the exchange's detail gives it to its reader. It shows nothing without one. */
export function CallTheInterlocutor({ exchangeId }: { exchangeId: string }) {
  const { t } = useTranslation()
  const numberToCall = useQuery(exchangeQuery(exchangeId)).data?.phoneNumber

  if (!numberToCall) return null

  return (
    <Button variant="outline" size="sm" asChild>
      <a href={telUrl(numberToCall)}>
        <Phone aria-hidden /> {t('exchanges.call')}
      </a>
    </Button>
  )
}
