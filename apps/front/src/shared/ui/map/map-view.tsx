import 'maplibre-gl/dist/maplibre-gl.css'
import { setWorkerUrl } from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url'
import { useMemo } from 'react'
import MapGL, { Marker, NavigationControl } from 'react-map-gl/maplibre'
import { useResolvedTheme } from '@/shared/stores/preferences'
import type { MapMarker } from './geo-map'

// open data vector tiles, no API key (https://openfreemap.org)
const styles = {
  light: 'https://tiles.openfreemap.org/styles/positron',
  dark: 'https://tiles.openfreemap.org/styles/dark',
}

// MapLibre looks for its worker next to its own file, which the Vite bundling moves: give its real URL
setWorkerUrl(workerUrl)

type Props = { markers: MapMarker[]; zoom?: number; onSelect?: (marker: MapMarker) => void }

export default function MapView({ markers, zoom = 13, onSelect }: Props) {
  const theme = useResolvedTheme()

  const initialViewState = useMemo(() => {
    if (markers.length <= 1) {
      const [first] = markers
      return {
        longitude: first?.longitude ?? 2.35,
        latitude: first?.latitude ?? 46.6,
        zoom: first ? zoom : 4.5,
      }
    }
    const longitudes = markers.map((marker) => marker.longitude)
    const latitudes = markers.map((marker) => marker.latitude)
    return {
      bounds: [
        [Math.min(...longitudes), Math.min(...latitudes)],
        [Math.max(...longitudes), Math.max(...latitudes)],
      ] as [[number, number], [number, number]],
      fitBoundsOptions: { padding: 48, maxZoom: 14 },
    }
  }, [markers, zoom])

  return (
    <MapGL
      initialViewState={initialViewState}
      mapStyle={styles[theme]}
      style={{ width: '100%', height: '100%' }}
      attributionControl={{ compact: true }}
      cooperativeGestures
    >
      <NavigationControl position="top-right" showCompass={false} />
      {markers.map((marker) => (
        <Marker key={marker.id} longitude={marker.longitude} latitude={marker.latitude} anchor="bottom">
          <button
            type="button"
            onClick={() => onSelect?.(marker)}
            title={marker.label}
            className="group grid place-items-center outline-none"
          >
            <span className="grid size-9 place-items-center overflow-hidden rounded-full border-2 border-background bg-primary font-semibold text-primary-foreground text-xs shadow-lg ring-primary/30 transition group-hover:scale-110 group-focus-visible:ring-4">
              {marker.imageUrl ? (
                <img src={marker.imageUrl} alt="" className="size-full object-cover" />
              ) : (
                marker.label.slice(0, 2).toUpperCase()
              )}
            </span>
            <span className="-mt-1 size-2 rotate-45 bg-primary" />
          </button>
        </Marker>
      ))}
    </MapGL>
  )
}
