import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { exchangesToProcessQuery } from '../queries'

/** How many exchanges are left to process, beside the entry of the dashboard; nothing when none is. */
export function ToProcessCount({ className }: { className?: string }) {
  const { t } = useTranslation()
  const count = useQuery(exchangesToProcessQuery()).data?.items.length ?? 0

  if (count === 0) return null

  return (
    <Badge className={cn('ms-auto bg-warning px-1.5 text-foreground tabular-nums', className)}>
      <span aria-hidden>{count}</span>
      <span className="sr-only">{t('toProcess.count', { count })}</span>
    </Badge>
  )
}
