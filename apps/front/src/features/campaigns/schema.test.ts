import { describe, expect, it } from 'vitest'
import { newCampaign, searchOf, targetedTagsOf, toCampaignInput } from './schema'

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

describe('the tags a campaign targets while it is typed', () => {
  it('are those chosen and the tag of its channel, once each', () => {
    expect(targetedTagsOf(['#ESN'], 'linkedIn')).toEqual(['#ESN', '#LINKEDIN'])
    expect(targetedTagsOf(['#ESN', '#PHONE'], 'call')).toEqual(['#ESN', '#PHONE'])
    expect(targetedTagsOf([], 'sms')).toEqual(['#SMS'])
    expect(targetedTagsOf([], 'email')).toEqual(['#EMAIL'])
  })

  it('are searched by their names without their mark', () => {
    expect(searchOf(['#ESN', '#EMAIL'])).toBe('ESN,EMAIL')
  })
})
