import { lazy, type ReactNode, Suspense } from 'react'
import { cn } from '@/shared/lib/utils'
import { Skeleton } from '@/shared/ui/skeleton'
import { keepThePinchesOnTheMap } from './keep-the-pinches-on-the-map'

export type MapMarker = {
  id: string
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
    <div
      ref={(map) => (map ? keepThePinchesOnTheMap(map) : undefined)}
      className={cn(
        'relative isolate touch-pan-x touch-pan-y overflow-hidden rounded-xl border bg-muted',
        className,
      )}
    >
      <Suspense fallback={<Skeleton className="absolute inset-0 rounded-none" />}>
        <MapViewLoadedOnDemand {...props} />
      </Suspense>
    </div>
  )
}
