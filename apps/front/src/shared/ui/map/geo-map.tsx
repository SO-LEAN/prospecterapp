import { lazy, Suspense } from 'react'
import { cn } from '@/shared/lib/utils'
import { Skeleton } from '@/shared/ui/skeleton'

export type MapMarker = {
  id: number
  latitude: number
  longitude: number
  label: string
  imageUrl?: string | null
}

// MapLibre is heavy: load it only when a map is actually displayed
const MapView = lazy(() => import('./map-view'))

type Props = {
  markers: MapMarker[]
  zoom?: number
  onSelect?: (marker: MapMarker) => void
  className?: string
}

export function GeoMap({ className, ...props }: Props) {
  return (
    <div className={cn('relative isolate overflow-hidden rounded-xl border bg-muted', className)}>
      <Suspense fallback={<Skeleton className="absolute inset-0 rounded-none" />}>
        <MapView {...props} />
      </Suspense>
    </div>
  )
}
