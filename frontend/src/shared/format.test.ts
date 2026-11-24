import { describe, expect, it } from 'vitest'
import { formatAr, formatPeriode, monthsBetween } from './format'

describe('format', () => {
  it('writes ariary as whole grouped numbers', () => {
    expect(formatAr(1250000)).toBe('1 250 000 Ar')
    expect(formatAr(0)).toBe('0 Ar')
  })

  it('names a month in French with a capital', () => {
    expect(formatPeriode('2026-09')).toBe('Septembre 2026')
  })

  it('leaves non-monthly periods readable as they are', () => {
    expect(formatPeriode('2026-T1')).toBe('2026-T1')
  })

  it('lists every month of a school year across the new year', () => {
    expect(monthsBetween('2026-09-01', '2027-01-31')).toEqual([
      '2026-09',
      '2026-10',
      '2026-11',
      '2026-12',
      '2027-01',
    ])
  })
})
