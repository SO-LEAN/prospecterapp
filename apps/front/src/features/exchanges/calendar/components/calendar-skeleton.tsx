import { Skeleton } from '@/shared/ui/skeleton'

const CELLS = 35

/** The calendar while its period loads: its header, its toolbar and a grid, never an empty screen. */
export function CalendarSkeleton() {
  return (
    <div className="space-y-4" aria-busy="true">
      <div className="space-y-2">
        <Skeleton className="h-7 w-40" />
        <Skeleton className="h-4 w-64" />
      </div>
      <Skeleton className="h-9 w-full" />
      <div className="grid grid-cols-7 gap-px overflow-hidden rounded-xl border">
        {Array.from({ length: CELLS }, (_, index) => (
          // biome-ignore lint/suspicious/noArrayIndexKey: static placeholders
          <Skeleton key={index} className="h-24 rounded-none" />
        ))}
      </div>
    </div>
  )
}
