import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from './badge'

/**
 * « MCP » on what an AI created for the member through the MCP server. Hovering and screen readers give « Créé par
 * une IA ». Prospecter knows that an AI acted, but never which one.
 */
export function CreatedByAnAIBadge({ className }: { className?: string }) {
  const { t } = useTranslation()

  return (
    <Badge
      variant="outline"
      title={t('common.createdByAnAI')}
      className={cn('shrink-0 px-1 py-0 font-normal text-[0.65rem]', className)}
    >
      {t('common.mcp')}
      <span className="sr-only">{t('common.createdByAnAI')}</span>
    </Badge>
  )
}
