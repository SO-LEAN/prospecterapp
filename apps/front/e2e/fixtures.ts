import { type BrowserContext, test as base, expect, type Route } from '@playwright/test'

/** The hosts of the local stack: a journey asks nothing else, a service outside it doubled. */
const HOSTS_OF_THE_STACK = new Set([
  'local.prospecter.io',
  'api.local.prospecter.io',
  'static.local.prospecter.io',
])
const OPENFREEMAP_STYLES = 'https://tiles.openfreemap.org/styles/**'

const FRANCE = { west: -5, east: 9, south: 41, north: 51 }

function degreesBetween(first: number, last: number) {
  return Array.from({ length: last - first + 1 }, (_, index) => first + index)
}

/**
 * The double of the styles of OpenFreeMap: no tile, a background crossed by the meridians and the parallels of
 * France, drawn from the style itself, so that the map moves visibly when it is zoomed or dragged.
 */
const MAP_STYLE = {
  version: 8,
  sources: {
    graticule: {
      type: 'geojson',
      data: {
        type: 'FeatureCollection',
        features: [
          ...degreesBetween(FRANCE.west, FRANCE.east).map((longitude) =>
            lineThrough([longitude, FRANCE.south], [longitude, FRANCE.north]),
          ),
          ...degreesBetween(FRANCE.south, FRANCE.north).map((latitude) =>
            lineThrough([FRANCE.west, latitude], [FRANCE.east, latitude]),
          ),
        ],
      },
    },
  },
  layers: [
    { id: 'background', type: 'background', paint: { 'background-color': '#e8ecef' } },
    {
      id: 'graticule',
      type: 'line',
      source: 'graticule',
      paint: { 'line-color': '#8a96a3', 'line-width': 1 },
    },
  ],
}

function lineThrough(...coordinates: [number, number][]) {
  return { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates } }
}

/** Refuses what a page asks outside the stack, and remembers the host asked. */
async function refuseTheHostsOutsideTheStack(context: BrowserContext, asked: Set<string>) {
  await context.route(
    (url) => !HOSTS_OF_THE_STACK.has(url.hostname),
    (route: Route) => {
      asked.add(new URL(route.request().url()).hostname)
      return route.abort('blockedbyclient')
    },
  )
}

/**
 * The journeys depend on no service outside the stack: the maps get their style from a double, and a journey whose
 * pages ask another host fails, naming it. A journey doubles a service it needs by its own route, which wins.
 */
export const test = base.extend<{ outsideTheStack: undefined }>({
  outsideTheStack: [
    async ({ context }, use) => {
      const asked = new Set<string>()
      await refuseTheHostsOutsideTheStack(context, asked)
      await context.route(OPENFREEMAP_STYLES, (route) => route.fulfill({ json: MAP_STYLE }))
      await use(undefined)
      expect([...asked], 'hosts outside the stack asked by the journey').toEqual([])
    },
    { auto: true },
  ],
})

export { expect }
