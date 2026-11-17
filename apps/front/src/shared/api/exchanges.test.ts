import { QueryClient } from '@tanstack/react-query'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { aDisplayedQuery, aQueryNoLongerDisplayed } from '@/test/displayed-queries'
import { readTheExchangesAgain } from './exchanges'
import { queryKeys } from './keys'
import { AFTER_THE_REACTIONS_MS } from './reactions'

const WHAT_A_GESTURE_ON_MY_EXCHANGES_CHANGES = [
  queryKeys.organizationTimelines,
  queryKeys.exchangesToMakeConform,
  queryKeys.exchange,
  queryKeys.myCalendar,
  queryKeys.myDay,
  queryKeys.exchangesToProcess,
]

describe('readTheExchangesAgain', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('reads each displayed query it changes once now and once after the reactions', async () => {
    const queryClient = new QueryClient()
    const readsOfEach = WHAT_A_GESTURE_ON_MY_EXCHANGES_CHANGES.map((queryKey) =>
      aDisplayedQuery(queryClient, [...queryKey, 'shown']),
    )
    await vi.runAllTimersAsync()

    readTheExchangesAgain(queryClient)
    await vi.runAllTimersAsync()

    expect(readsOfEach.map((reads) => reads())).toEqual(WHAT_A_GESTURE_ON_MY_EXCHANGES_CHANGES.map(() => 3))
  })

  it('leaves the calendar not displayed to be read at its opening', async () => {
    const queryClient = new QueryClient()
    const theCalendar = aQueryNoLongerDisplayed(queryClient, [...queryKeys.myCalendar, 'this week'])
    await vi.runAllTimersAsync()
    theCalendar.hide()

    readTheExchangesAgain(queryClient)
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    expect(theCalendar.reads()).toBe(1)
    expect(theCalendar.isToReadAtItsOpening()).toBe(true)
  })
})
