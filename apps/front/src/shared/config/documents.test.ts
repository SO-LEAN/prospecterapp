import { QueryClient } from '@tanstack/react-query'
import { describe, expect, it, vi } from 'vitest'
import { servedDocumentsQuery } from './documents'

const theInstance = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

const asked = () => {
  const [url, init] = theInstance.mock.calls.at(-1) ?? []

  return { path: new URL(String(url)).pathname, method: init?.method }
}

describe('servedDocumentsQuery', () => {
  it('asks the instance whether it serves the overview of the walls, without reading it', async () => {
    theInstance.mockResolvedValue(new Response(null, { status: 200 }))

    expect(await new QueryClient().fetchQuery(servedDocumentsQuery())).toBe(true)
    expect(asked()).toEqual({ path: '/docs/event-storming/', method: 'HEAD' })
  })
})
