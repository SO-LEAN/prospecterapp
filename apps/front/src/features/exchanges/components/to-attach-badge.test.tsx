import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Exchange } from '../queries'
import { ExchangeLine } from './exchange-line'

const ID = '0190a1c0-0000-7000-8000-00000000e000'

const aVideoCall = (organizationIds: string[], contactToAttach: boolean): Exchange => ({
  id: ID,
  channel: 'videoCall',
  direction: 'outbound',
  occurredAt: new Date(2026, 10, 20, 14, 0).toISOString(),
  status: 'planned',
  durationInMinutes: 30,
  textFlagged: false,
  textToMakeConform: false,
  toMakeConform: false,
  organizationIds,
  attachmentCount: 0,
  contactToAttach,
  bookedByLink: false,
  _links: { self: { href: `/api/exchanges/${ID}`, method: 'GET' } },
})

describe('an exchange with a contact to attach', () => {
  it('carries the badge « à rattacher », as the API says (ADR 0146)', async () => {
    await renderInARouter(<ExchangeLine exchange={aVideoCall([], true)} />)

    expect(screen.getByText('À rattacher')).toBeInTheDocument()
  })

  it('carries none when the API says its contact is not to attach', async () => {
    await renderInARouter(<ExchangeLine exchange={aVideoCall([], false)} />)

    expect(screen.queryByText('À rattacher')).not.toBeInTheDocument()
  })
})
