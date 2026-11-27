import { Link, type LinkOptions } from '@tanstack/react-router'
import { ChevronRight, type LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { formatNumber } from '@/shared/lib/format'
import { Card, CardContent } from '@/shared/ui/card'

/** Where a figure leads, and that place's name in the figure's accessible label. */
export type Destination = { link: LinkOptions; name: string }

type Props = { label: string; value: number; icon: LucideIcon; destination?: Destination }

/**
 * A figure under its label. A figure that has a destination is a link to what it counts, and looks like one: a hover,
 * a focus ring and a chevron. A figure without one shows none of them.
 */
export function StatCard({ label, value, icon, destination }: Props) {
  const { t } = useTranslation()

  if (!destination) {
    return (
      <Card>
        <Figure label={label} value={value} icon={icon} />
      </Card>
    )
  }

  return (
    <Link
      {...destination.link}
      aria-label={t('dashboard.stats.leadsTo', {
        figure: formatNumber(value),
        label,
        destination: destination.name,
      })}
      className="group block h-full rounded-xl ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
    >
      <Card className="h-full transition group-hover:border-primary/40 group-hover:shadow-md">
        <Figure label={label} value={value} icon={icon}>
          <ChevronRight
            className="ml-auto size-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-foreground"
            aria-hidden
          />
        </Figure>
      </Card>
    </Link>
  )
}

function Figure({
  label,
  value,
  icon: Icon,
  children,
}: Omit<Props, 'destination'> & { children?: ReactNode }) {
  return (
    <CardContent className="flex items-center gap-4">
      <div className="grid size-11 place-items-center rounded-xl bg-accent text-accent-foreground">
        <Icon className="size-5" aria-hidden />
      </div>
      <div>
        <p className="font-semibold text-2xl tabular-nums tracking-tight">{formatNumber(value)}</p>
        <p className="text-muted-foreground text-sm">{label}</p>
      </div>
      {children}
    </CardContent>
  )
}
