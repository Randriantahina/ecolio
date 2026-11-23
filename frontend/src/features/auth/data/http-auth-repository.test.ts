import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/shared/api/http-client'
import { HttpAuthRepository } from './http-auth-repository'

const user = {
  uuid: 'u-1',
  nom: 'Rasoa',
  email: 'rasoa@ecole.mg',
  telephone: null,
  langue: 'fr',
  roles: ['directeur'],
  ecole: { uuid: 'e-1', nom: 'Ecole Pilote' },
}

function fakeHttp(overrides: Partial<HttpClient> = {}): HttpClient {
  return {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    ...overrides,
  } as HttpClient
}

describe('HttpAuthRepository', () => {
  it('posts credentials with the API field names and returns the parsed session', async () => {
    const post = vi.fn().mockResolvedValue({ token: '1|abc', utilisateur: user })
    const repository = new HttpAuthRepository(fakeHttp({ post }))

    const result = await repository.login({
      login: 'rasoa@ecole.mg',
      password: 'secret',
      deviceName: 'phone',
    })

    expect(post).toHaveBeenCalledWith('/auth/login', {
      login: 'rasoa@ecole.mg',
      password: 'secret',
      device_name: 'phone',
    })
    expect(result.token).toBe('1|abc')
    expect(result.utilisateur.nom).toBe('Rasoa')
  })

  it('refuses a malformed login response instead of storing a broken session', async () => {
    const post = vi.fn().mockResolvedValue({ utilisateur: user })
    const repository = new HttpAuthRepository(fakeHttp({ post }))

    await expect(repository.login({ login: 'a', password: 'b' })).rejects.toThrow()
  })

  it('loads the current profile', async () => {
    const get = vi.fn().mockResolvedValue({ utilisateur: user })
    const repository = new HttpAuthRepository(fakeHttp({ get }))

    expect((await repository.me()).uuid).toBe('u-1')
    expect(get).toHaveBeenCalledWith('/me')
  })

  it('logs out on the server', async () => {
    const post = vi.fn().mockResolvedValue(undefined)
    await new HttpAuthRepository(fakeHttp({ post })).logout()

    expect(post).toHaveBeenCalledWith('/auth/logout')
  })
})
