import { QueryClient } from '@tanstack/react-query'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { aDisplayedQuery, aQueryNoLongerDisplayed } from '@/test/displayed-queries'
import { AFTER_THE_REACTIONS_MS, readAgainAfterTheReactions } from './reactions'

describe('readAgainAfterTheReactions', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('reads the queries again at once, then once after the reactions, and no more', async () => {
    const queryClient = new QueryClient()
    const readsOfTheContacts = aDisplayedQuery(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.advanceTimersByTimeAsync(0)
    expect(readsOfTheContacts()).toBe(2)

    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)
    expect(readsOfTheContacts()).toBe(3)
    await vi.runAllTimersAsync()
    expect(readsOfTheContacts()).toBe(3)
  })

  it('reads a query a gesture names twice once at each moment', async () => {
    const queryClient = new QueryClient()
    const readsOfTheContacts = aDisplayedQuery(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'], ['organizations'])
    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.advanceTimersByTimeAsync(0)
    expect(readsOfTheContacts()).toBe(2)

    await vi.runAllTimersAsync()
    expect(readsOfTheContacts()).toBe(3)
  })

  it('reads the queries once after the reactions to the last of gestures following each other', async () => {
    const queryClient = new QueryClient()
    const readsOfTheContacts = aDisplayedQuery(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS / 2)
    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS / 2)
    expect(readsOfTheContacts()).toBe(3)

    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS / 2)
    expect(readsOfTheContacts()).toBe(4)
  })

  it('reads only the queries named', async () => {
    const queryClient = new QueryClient()
    const readsOfTheOrganizations = aDisplayedQuery(queryClient, ['organizations'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    expect(readsOfTheOrganizations()).toBe(1)
  })

  it('leaves a query no longer displayed to be read at its opening', async () => {
    const queryClient = new QueryClient()
    const theCalendar = aQueryNoLongerDisplayed(queryClient, ['calendar'])
    await vi.runAllTimersAsync()
    theCalendar.hide()

    readAgainAfterTheReactions(queryClient, ['calendar'])
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    expect(theCalendar.reads()).toBe(1)
    expect(theCalendar.isToReadAtItsOpening()).toBe(true)
  })
})
