import { describe, expect, it } from 'vitest'
import type { DayExchange } from '../my-day/queries'
import { nextReminderAt, type Reminded, reminderOf, remindersDue } from './schedule'

const at = (hours: number, minutes: number, seconds = 0) => new Date(2026, 10, 13, hours, minutes, seconds)
const ON_TIME = { enabled: true, minutesBefore: 0 }
const TEN_MINUTES_BEFORE = { enabled: true, minutesBefore: 10 }

function plannedAt14(status: DayExchange['exchange']['status'] = 'planned'): DayExchange {
  return {
    exchange: {
      id: '0190a1c0-0000-7000-8000-000000000001',
      channel: 'call',
      direction: 'outbound',
      occurredAt: at(14, 0).toISOString(),
      status,
      durationInMinutes: 30,
      textFlagged: false,
      textToMakeConform: false,
      toMakeConform: false,
      organizationIds: [],
      attachmentCount: 0,
      _links: { self: { href: '/api/exchanges/0190a1c0-0000-7000-8000-000000000001', method: 'GET' } },
    },
    interlocutor: {
      kind: 'person',
      id: '0190a1c0-0000-7000-8000-00000000a000',
      name: 'Ada',
      presence: 'present',
    },
  }
}

const shown = (item: DayExchange, again: Date | null): Reminded => ({
  [reminderOf(item.exchange)]: { occurredAt: item.exchange.occurredAt, again: again?.toISOString() ?? null },
})

describe('the reminders due', () => {
  it('reminds on time at the start of the exchange, and no more a minute later', () => {
    const call = plannedAt14()

    expect(remindersDue([call], ON_TIME, {}, at(13, 59, 59))).toEqual([])
    expect(remindersDue([call], ON_TIME, {}, at(14, 0))).toEqual([call])
    expect(remindersDue([call], ON_TIME, {}, at(14, 0, 59))).toEqual([call])
    expect(remindersDue([call], ON_TIME, {}, at(14, 1))).toEqual([])
  })

  it('reminds again at the moment asked, within its minute, even once the exchange started', () => {
    const call = plannedAt14()
    const askedAgain = shown(call, at(14, 3))

    expect(remindersDue([call], TEN_MINUTES_BEFORE, askedAgain, at(14, 2))).toEqual([])
    expect(remindersDue([call], TEN_MINUTES_BEFORE, askedAgain, at(14, 3, 30))).toEqual([call])
    expect(remindersDue([call], TEN_MINUTES_BEFORE, askedAgain, at(14, 4))).toEqual([])
  })

  it('never reminds again of a reminder shown', () => {
    const call = plannedAt14()

    expect(remindersDue([call], TEN_MINUTES_BEFORE, shown(call, null), at(13, 55))).toEqual([])
    expect(nextReminderAt([call], TEN_MINUTES_BEFORE, shown(call, null), at(13, 40))).toBeNull()
  })

  it.each(['done', 'missed', 'cancelled'] as const)('never reminds of an exchange %s', (status) => {
    expect(remindersDue([plannedAt14(status)], TEN_MINUTES_BEFORE, {}, at(13, 55))).toEqual([])
  })

  it('wakes at the next reminder to come', () => {
    expect(nextReminderAt([plannedAt14()], TEN_MINUTES_BEFORE, {}, at(13, 40))).toEqual(at(13, 50))
    expect(nextReminderAt([plannedAt14()], TEN_MINUTES_BEFORE, {}, at(13, 50))).toBeNull()
  })
})
