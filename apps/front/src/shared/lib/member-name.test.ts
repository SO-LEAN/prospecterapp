import { describe, expect, it } from 'vitest'
import { memberFirstName, memberName } from './member-name'

describe('memberName', () => {
  it('shows the name of the profile', () => {
    expect(memberName({ name: { firstName: 'Ada', lastName: 'Lovelace' }, email: 'ada@babbage.test' })).toBe(
      'Ada Lovelace',
    )
  })

  it('shows the half of a name the profile has', () => {
    expect(memberName({ name: { firstName: null, lastName: 'Lovelace' } })).toBe('Lovelace')
  })

  it('shows the email of a profile without a name', () => {
    expect(memberName({ name: { firstName: null, lastName: null }, email: 'ada@babbage.test' })).toBe(
      'ada@babbage.test',
    )
  })

  it('shows a member without name nor email as such', () => {
    expect(memberName({ name: { firstName: null, lastName: null }, email: null })).toBe('Membre sans nom')
  })
})

describe('memberFirstName', () => {
  it('greets a member by their first name', () => {
    expect(memberFirstName({ name: { firstName: 'Ada', lastName: 'Lovelace' } })).toBe('Ada')
  })

  it('greets a member without a first name as they are shown', () => {
    expect(memberFirstName({ name: { firstName: null, lastName: null }, email: null })).toBe(
      'Membre sans nom',
    )
  })
})
