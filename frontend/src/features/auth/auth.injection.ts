import type { InjectionKey } from 'vue'
import type { AuthRepository } from './domain/auth.repository'

/** Views get their repository by injection so tests (and later the offline layer) can swap it. */
export const authRepositoryKey: InjectionKey<AuthRepository> = Symbol('authRepository')
