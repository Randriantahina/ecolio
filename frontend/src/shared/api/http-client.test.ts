import { describe, expect, it, vi } from 'vitest'
import { ApiError, LockedError, NetworkError, UnauthorizedError, ValidationError } from './errors'
import { createHttpClient } from './http-client'

function jsonResponse(status: number, body?: unknown): Response {
  return new Response(body === undefined ? null : JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

/** Awaits a promise that must reject and returns the typed rejection; fails the test if it resolves. */
async function rejectionOf<E extends Error>(promise: Promise<unknown>): Promise<E> {
  try {
    await promise
  } catch (error) {
    return error as E
  }
  throw new Error('Expected the promise to reject')
}

function clientWith(
  fetchImpl: typeof fetch,
  token: string | null = null,
  onUnauthorized = vi.fn(),
) {
  const client = createHttpClient({
    baseUrl: 'https://api.test/api/v1/',
    getToken: () => token,
    fetchImpl,
    onUnauthorized,
  })
  return { client, onUnauthorized }
}

describe('http client', () => {
  it('joins base URL and path without doubling slashes', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(200, {}))
    await clientWith(fetchImpl).client.get('/me')

    expect(fetchImpl.mock.calls[0][0]).toBe('https://api.test/api/v1/me')
  })

  it('sends JSON and the bearer token when one exists', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(200, { ok: true }))
    await clientWith(fetchImpl, 'abc123').client.post('/auth/login', { login: 'a' })

    const init = fetchImpl.mock.calls[0][1] as RequestInit
    const headers = new Headers(init.headers)
    expect(init.method).toBe('POST')
    expect(init.body).toBe(JSON.stringify({ login: 'a' }))
    expect(headers.get('Accept')).toBe('application/json')
    expect(headers.get('Content-Type')).toBe('application/json')
    expect(headers.get('Authorization')).toBe('Bearer abc123')
  })

  it('omits the Authorization header for anonymous calls', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(200, {}))
    await clientWith(fetchImpl, null).client.get('/ecole')

    const headers = new Headers((fetchImpl.mock.calls[0][1] as RequestInit).headers)
    expect(headers.has('Authorization')).toBe(false)
  })

  it('returns the parsed body, and undefined for 204 No Content', async () => {
    const ok = clientWith(vi.fn().mockResolvedValue(jsonResponse(200, { a: 1 })))
    const empty = clientWith(vi.fn().mockResolvedValue(new Response(null, { status: 204 })))

    expect(await ok.client.get('/x')).toEqual({ a: 1 })
    expect(await empty.client.post('/auth/logout')).toBeUndefined()
  })

  it('maps 422 to a ValidationError carrying per-field messages', async () => {
    const body = { message: 'invalid', errors: { login: ['Required'] } }
    const { client } = clientWith(vi.fn().mockResolvedValue(jsonResponse(422, body)))

    const error = await rejectionOf<ValidationError>(client.post('/auth/login', {}))

    expect(error).toBeInstanceOf(ValidationError)
    expect(error.errors).toEqual({ login: ['Required'] })
  })

  it('maps 401 to UnauthorizedError and signals the session so it can be dropped', async () => {
    const { client, onUnauthorized } = clientWith(
      vi.fn().mockResolvedValue(jsonResponse(401, {})),
      'expired',
    )

    await expect(client.get('/me')).rejects.toBeInstanceOf(UnauthorizedError)
    expect(onUnauthorized).toHaveBeenCalledOnce()
  })

  it('maps 423 to LockedError with the server message', async () => {
    const { client } = clientWith(
      vi.fn().mockResolvedValue(jsonResponse(423, { message: 'Compte verrouillé' })),
    )

    const error = await rejectionOf<LockedError>(client.post('/auth/login', {}))

    expect(error).toBeInstanceOf(LockedError)
    expect(error.message).toBe('Compte verrouillé')
  })

  it('maps any other failure status to ApiError with that status', async () => {
    const { client } = clientWith(vi.fn().mockResolvedValue(jsonResponse(500, { message: 'boom' })))

    const error = await rejectionOf<ApiError>(client.get('/x'))

    expect(error).toBeInstanceOf(ApiError)
    expect(error.status).toBe(500)
  })

  it('maps a fetch rejection to NetworkError so the UI can say "offline", not "broken"', async () => {
    const { client } = clientWith(vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    await expect(client.get('/x')).rejects.toBeInstanceOf(NetworkError)
  })
})
