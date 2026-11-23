import type { LoginResponse, Utilisateur } from './auth.schemas'

export interface LoginCredentials {
  login: string
  password: string
  deviceName?: string
}

/** Port the ViewModels depend on; the HTTP implementation lives in data/. */
export interface AuthRepository {
  login(credentials: LoginCredentials): Promise<LoginResponse>
  logout(): Promise<void>
  me(): Promise<Utilisateur>
}
