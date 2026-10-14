import type { LucideIcon } from 'lucide-react'
import { formatNumber } from '@/shared/lib/format'
import { Card, CardContent } from '@/shared/ui/card'

type Props = { label: string; value: number; icon: LucideIcon }

export function StatCard({ label, value, icon: Icon }: Props) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4">
        <div className="grid size-11 place-items-center rounded-xl bg-accent text-accent-foreground">
          <Icon className="size-5" aria-hidden />
        </div>
        <div>
          <p className="font-semibold text-2xl tabular-nums tracking-tight">{formatNumber(value)}</p>
          <p className="text-muted-foreground text-sm">{label}</p>
        </div>
      </CardContent>
    </Card>
  )
}
