import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Exchange } from '../queries'
import { ExchangeLine } from './exchange-line'

const ID = '0190a1c0-0000-7000-8000-00000000e000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'

const aVideoCall = (organizationIds: string[]): Exchange => ({
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
  _links: { self: { href: `/api/exchanges/${ID}`, method: 'GET' } },
})

describe('an exchange with a contact to attach', () => {
  it('carries the badge « à rattacher », kept for no organization (ADR 0146)', async () => {
    await renderInARouter(<ExchangeLine exchange={aVideoCall([])} />)

    expect(screen.getByText('À rattacher')).toBeInTheDocument()
  })

  it('carries none once kept for an organization', async () => {
    await renderInARouter(<ExchangeLine exchange={aVideoCall([ACME])} />)

    expect(screen.queryByText('À rattacher')).not.toBeInTheDocument()
  })
})
