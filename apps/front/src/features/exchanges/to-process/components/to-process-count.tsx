import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { countOf, exchangesToProcessQuery } from '../queries'

/** How many exchanges and to-dos are left to process, beside the dashboard's entry. It shows nothing when none is left. */
export function ToProcessCount({ className }: { className?: string }) {
  const { t } = useTranslation()
  const { data } = useQuery(exchangesToProcessQuery())
  const count = data ? countOf(data) : 0

  if (count === 0) return null

  return (
    <Badge className={cn('ms-auto bg-warning px-1.5 text-foreground tabular-nums', className)}>
      <span aria-hidden>{count}</span>
      <span className="sr-only">{t('toProcess.count', { count })}</span>
    </Badge>
  )
}
