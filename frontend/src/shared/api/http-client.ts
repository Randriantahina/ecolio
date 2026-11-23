import { ApiError, LockedError, NetworkError, UnauthorizedError, ValidationError } from './errors'

export interface HttpClient {
  get<T = unknown>(path: string): Promise<T>
  post<T = unknown>(path: string, body?: unknown): Promise<T>
  put<T = unknown>(path: string, body?: unknown): Promise<T>
  patch<T = unknown>(path: string, body?: unknown): Promise<T>
  delete<T = unknown>(path: string): Promise<T>
}

export interface HttpClientOptions {
  baseUrl: string
  getToken: () => string | null
  /** Injected so tests and the offline layer can swap the transport. */
  fetchImpl?: typeof fetch
  /** Called on 401 so the session store can drop an expired token without the client knowing about stores. */
  onUnauthorized?: () => void
}

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export function createHttpClient(options: HttpClientOptions): HttpClient {
  const base = options.baseUrl.replace(/\/+$/, '')

  async function request<T>(method: Method, path: string, body?: unknown): Promise<T> {
    const headers = new Headers({ Accept: 'application/json' })
    const token = options.getToken()
    if (token) headers.set('Authorization', `Bearer ${token}`)
    if (body !== undefined) headers.set('Content-Type', 'application/json')

    let response: Response
    try {
      response = await (options.fetchImpl ?? fetch)(`${base}/${path.replace(/^\/+/, '')}`, {
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
      })
    } catch {
      throw new NetworkError()
    }

    if (response.status === 204) return undefined as T

    const payload = (await response.json().catch(() => ({}))) as {
      message?: string
      errors?: Record<string, string[]>
    }

    if (response.ok) return payload as T

    const message = payload.message ?? `HTTP ${response.status}`
    switch (response.status) {
      case 401:
        options.onUnauthorized?.()
        throw new UnauthorizedError(message)
      case 422:
        throw new ValidationError(message, payload.errors ?? {})
      case 423:
        throw new LockedError(message)
      default:
        throw new ApiError(message, response.status)
    }
  }

  return {
    get: (path) => request('GET', path),
    post: (path, body) => request('POST', path, body),
    put: (path, body) => request('PUT', path, body),
    patch: (path, body) => request('PATCH', path, body),
    delete: (path) => request('DELETE', path),
  }
}
