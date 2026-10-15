import { lazy, type ReactNode, Suspense } from 'react'
import { cn } from '@/shared/lib/utils'
import { Skeleton } from '@/shared/ui/skeleton'

export type MapMarker = {
  id: number
  latitude: number
  longitude: number
  label: string
  imageUrl?: string | null
}

const MapViewLoadedOnDemand = lazy(() => import('./map-view'))

type Props = {
  markers: MapMarker[]
  zoom?: number
  /** Content of the popup opened by a click on a marker; without it markers are not clickable. */
  renderPopup?: (marker: MapMarker) => ReactNode
  className?: string
}

export function GeoMap({ className, ...props }: Props) {
  return (
    <div className={cn('relative isolate overflow-hidden rounded-xl border bg-muted', className)}>
      <Suspense fallback={<Skeleton className="absolute inset-0 rounded-none" />}>
        <MapViewLoadedOnDemand {...props} />
      </Suspense>
    </div>
  )
}
