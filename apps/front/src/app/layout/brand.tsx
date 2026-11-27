import { cn } from '@/shared/lib/utils'

const PETROL = '#0E7C84'
const GOLD = '#F5B32E'
const WHITE = '#FFFFFF'
const VEIN = 'M12.5 47 27 20 34.5 30 41 24.5 51.5 47'
const GROUND_UNDER_THE_VEIN = `${VEIN}Z`

/** A vein that climbs towards a gold nugget, on a petrol tile: the drawing in `public/favicon.svg`, the same in both themes. */
export function BrandMark({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 64 64" className={cn('size-8 shrink-0', className)} aria-hidden>
      <rect width="64" height="64" rx="14" fill={PETROL} />
      <path d={GROUND_UNDER_THE_VEIN} fill={WHITE} fillOpacity="0.18" />
      <path
        d={VEIN}
        fill="none"
        stroke={WHITE}
        strokeWidth="3.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <circle cx="46" cy="16.5" r="5.5" fill={GOLD} />
    </svg>
  )
}
