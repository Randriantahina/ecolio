import { cn } from '@/lib/utils'

describe('cn', () => {
    it('lets the last Tailwind utility win so component overrides behave predictably', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4')
    })

    it('drops falsy values used for conditional classes', () => {
        const isActive = false
        expect(cn('a', isActive && 'b', undefined, 'c')).toBe('a c')
    })
})
