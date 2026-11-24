import { describe, expect, it } from 'vitest'
import { folderOf } from './model.ts'

describe('the folder of a context', () => {
  it('writes a context of several words in kebab case', () => {
    expect(folderOf('PersonalData')).toBe('personal-data')
  })

  it('writes a context of one word in lower case', () => {
    expect(folderOf('Booking')).toBe('booking')
  })
})
