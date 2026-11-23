import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { LockedError, NetworkError, ValidationError } from '@/shared/api/errors'
import { useSessionStore } from './session.store'
import type { AuthRepository } from './domain/auth.repository'
import { useLoginViewModel } from './login.viewmodel'

const user = {
  uuid: 'u-1',
  nom: 'Rasoa',
  email: 'rasoa@ecole.mg',
  telephone: null,
  langue: 'fr',
  roles: ['directeur'],
  ecole: { uuid: 'e-1', nom: 'Ecole Pilote' },
}

function fakeRepository(login: AuthRepository['login']): AuthRepository {
  return { login, logout: vi.fn(), me: vi.fn() }
}

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('login view model', () => {
  it('cannot submit until both fields are filled', () => {
    const vm = useLoginViewModel(fakeRepository(vi.fn()), useSessionStore())

    expect(vm.canSubmit.value).toBe(false)
    vm.login.value = 'rasoa@ecole.mg'
    expect(vm.canSubmit.value).toBe(false)
    vm.password.value = 'secret'
    expect(vm.canSubmit.value).toBe(true)
  })

  it('stores the session and reports success', async () => {
    const login = vi.fn().mockResolvedValue({ token: '1|abc', utilisateur: user })
    const session = useSessionStore()
    const vm = useLoginViewModel(fakeRepository(login), session)
    vm.login.value = '  rasoa@ecole.mg '
    vm.password.value = 'secret'

    const ok = await vm.submit()

    expect(ok).toBe(true)
    expect(login).toHaveBeenCalledWith({
      login: 'rasoa@ecole.mg',
      password: 'secret',
      deviceName: 'web',
    })
    expect(session.token).toBe('1|abc')
    expect(vm.loading.value).toBe(false)
  })

  it('blocks a double submit while a request is in flight', async () => {
    let resolve!: (value: unknown) => void
    const login = vi.fn().mockReturnValue(new Promise((r) => (resolve = r)))
    const vm = useLoginViewModel(fakeRepository(login), useSessionStore())
    vm.login.value = 'a'
    vm.password.value = 'b'

    const first = vm.submit()
    expect(vm.loading.value).toBe(true)
    expect(vm.canSubmit.value).toBe(false)
    await vm.submit()
    expect(login).toHaveBeenCalledTimes(1)

    resolve({ token: 't', utilisateur: user })
    await first
  })

  it('exposes server validation messages per field and keeps the user anonymous', async () => {
    const login = vi
      .fn()
      .mockRejectedValue(new ValidationError('x', { login: ['Identifiants incorrects.'] }))
    const session = useSessionStore()
    const vm = useLoginViewModel(fakeRepository(login), session)
    vm.login.value = 'a'
    vm.password.value = 'b'

    expect(await vm.submit()).toBe(false)
    expect(vm.fieldErrors.value.login).toEqual(['Identifiants incorrects.'])
    expect(session.isAuthenticated).toBe(false)
  })

  it('tells a locked account apart, with the server message', async () => {
    const login = vi.fn().mockRejectedValue(new LockedError('Compte verrouillé'))
    const vm = useLoginViewModel(fakeRepository(login), useSessionStore())
    vm.login.value = 'a'
    vm.password.value = 'b'

    await vm.submit()

    expect(vm.errorKind.value).toBe('locked')
    expect(vm.serverMessage.value).toBe('Compte verrouillé')
  })

  it('tells being offline apart from wrong credentials', async () => {
    const login = vi.fn().mockRejectedValue(new NetworkError())
    const vm = useLoginViewModel(fakeRepository(login), useSessionStore())
    vm.login.value = 'a'
    vm.password.value = 'b'

    await vm.submit()

    expect(vm.errorKind.value).toBe('offline')
  })

  it('reports anything unexpected without leaking technical details', async () => {
    const login = vi.fn().mockRejectedValue(new Error('SQLSTATE[...]'))
    const vm = useLoginViewModel(fakeRepository(login), useSessionStore())
    vm.login.value = 'a'
    vm.password.value = 'b'

    await vm.submit()

    expect(vm.errorKind.value).toBe('unexpected')
    expect(vm.serverMessage.value).toBeNull()
  })

  it('clears the previous error when the user tries again', async () => {
    const login = vi
      .fn()
      .mockRejectedValueOnce(new LockedError('Compte verrouillé'))
      .mockResolvedValueOnce({ token: 't', utilisateur: user })
    const vm = useLoginViewModel(fakeRepository(login), useSessionStore())
    vm.login.value = 'a'
    vm.password.value = 'b'

    await vm.submit()
    await vm.submit()

    expect(vm.errorKind.value).toBeNull()
    expect(vm.fieldErrors.value).toEqual({})
  })
})
