import { describe, expect, it } from 'vitest'
import { newCampaign, toCampaignInput } from './schema'

describe('the input of a campaign', () => {
  it('sends the subject of an e-mail, trimmed', () => {
    expect(toCampaignInput({ ...newCampaign(), name: ' Relance ', subject: ' Une question ' })).toMatchObject(
      {
        name: 'Relance',
        subject: 'Une question',
      },
    )
  })

  it('sends no subject for another channel, nor a blank one', () => {
    expect(toCampaignInput({ ...newCampaign(), channel: 'call', subject: 'Une question' }).subject).toBeNull()
    expect(toCampaignInput({ ...newCampaign(), subject: '  ' }).subject).toBeNull()
  })
})
