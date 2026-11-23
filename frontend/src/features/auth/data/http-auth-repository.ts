import type { HttpClient } from '@/shared/api/http-client'
import type { AuthRepository, LoginCredentials } from '../domain/auth.repository'
import { loginResponseSchema, meResponseSchema } from '../domain/auth.schemas'

export class HttpAuthRepository implements AuthRepository {
  private readonly http: HttpClient

  constructor(http: HttpClient) {
    this.http = http
  }

  async login(credentials: LoginCredentials) {
    const payload = await this.http.post('/auth/login', {
      login: credentials.login,
      password: credentials.password,
      device_name: credentials.deviceName,
    })

    return loginResponseSchema.parse(payload)
  }

  async logout() {
    await this.http.post('/auth/logout')
  }

  async me() {
    return meResponseSchema.parse(await this.http.get('/me')).utilisateur
  }
}
