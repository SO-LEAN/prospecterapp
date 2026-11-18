import { MessageSquareText } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { ExchangeDetailSheet } from '@/features/exchanges/components/exchange-detail'
import type { components } from '@/shared/api/schema'
import { Button } from '@/shared/ui/button'

type Link = components['schemas']['LinkResource']

/** The exchange noted for a recipient processed, opened beside the campaign as from a timeline (ADR 0152). */
export function OpenTheExchange({ exchange, name }: { exchange: Link | undefined; name: string }) {
  const { t } = useTranslation()
  const [opened, setOpened] = useState<string | null>(null)

  if (!exchange) {
    return null
  }

  return (
    <>
      <Button
        variant="ghost"
        size="icon"
        aria-label={t('campaigns.recipients.openExchange', { name })}
        onClick={() => setOpened(exchangeIdOf(exchange))}
      >
        <MessageSquareText aria-hidden />
      </Button>
      <ExchangeDetailSheet exchangeId={opened} onClose={() => setOpened(null)} />
    </>
  )
}

function exchangeIdOf({ href }: Link): string {
  return href.split('/').pop() ?? ''
}
