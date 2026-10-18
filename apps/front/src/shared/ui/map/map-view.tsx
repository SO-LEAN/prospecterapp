import 'maplibre-gl/dist/maplibre-gl.css'
import './map-view.css'
import { setWorkerUrl } from 'maplibre-gl'
import bundledWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url'
import { type ReactNode, useRef, useState } from 'react'
import MapGL, { type MapRef, Marker, type MarkerEvent, NavigationControl, Popup } from 'react-map-gl/maplibre'
import { useResolvedTheme } from '@/shared/stores/preferences'
import type { MapMarker } from './geo-map'

const openFreeMapStyles = {
  light: 'https://tiles.openfreemap.org/styles/positron',
  dark: 'https://tiles.openfreemap.org/styles/dark',
}

setWorkerUrl(bundledWorkerUrl)

const MARKER_HEIGHT = 48
const WHOLE_FRANCE = { longitude: 2.35, latitude: 46.6, zoom: 4.5 }

const preventTheMapFromClosingThePopup = (event: MarkerEvent<MouseEvent>) =>
  event.originalEvent.stopPropagation()

type Props = { markers: MapMarker[]; zoom?: number; renderPopup?: (marker: MapMarker) => ReactNode }

function initialViewStateFor(markers: MapMarker[], zoom: number) {
  if (markers.length <= 1) {
    const [first] = markers
    return first ? { longitude: first.longitude, latitude: first.latitude, zoom } : WHOLE_FRANCE
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
}

export default function MapView({ markers, zoom = 13, renderPopup }: Props) {
  const theme = useResolvedTheme()
  const [selected, setSelected] = useState<MapMarker | null>(null)
  const mapRef = useRef<MapRef>(null)

  const makeRoomForThePopupAbove = (marker: MapMarker) =>
    mapRef.current?.easeTo({
      center: [marker.longitude, marker.latitude],
      offset: [0, mapRef.current.getContainer().clientHeight / 2 - MARKER_HEIGHT],
      duration: 400,
    })

  const select = (marker: MapMarker) => {
    setSelected(marker)
    makeRoomForThePopupAbove(marker)
  }

  const initialViewState = initialViewStateFor(markers, zoom)

  return (
    <MapGL
      ref={mapRef}
      initialViewState={initialViewState}
      mapStyle={openFreeMapStyles[theme]}
      style={{ width: '100%', height: '100%' }}
      attributionControl={{ compact: true }}
      cooperativeGestures
    >
      <NavigationControl position="top-right" showCompass={false} />
      {markers.map((marker) => (
        <Marker
          key={marker.id}
          longitude={marker.longitude}
          latitude={marker.latitude}
          anchor="bottom"
          onClick={(event) => {
            preventTheMapFromClosingThePopup(event)
            if (renderPopup) {
              select(marker)
            }
          }}
        >
          <button
            type="button"
            title={marker.label}
            aria-label={marker.label}
            disabled={!renderPopup}
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
      {selected && renderPopup && (
        <Popup
          longitude={selected.longitude}
          latitude={selected.latitude}
          anchor="bottom"
          offset={MARKER_HEIGHT}
          maxWidth="none"
          onClose={() => setSelected(null)}
        >
          {renderPopup(selected)}
        </Popup>
      )}
    </MapGL>
  )
}
