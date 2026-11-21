import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from './badge'

/**
 * « MCP » on what an AI created for the member through the MCP server, « Créé par une IA » on hover and to a screen
 * reader: Prospecter knows an AI acted, never which one.
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
