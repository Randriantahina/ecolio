import { ApiError } from '@/shared/api/errors'
import type { HttpClient } from '@/shared/api/http-client'
import {
  type CreationEleve,
  DoublonDetecte,
  type EleveExistant,
  type Eleve,
  type ElevesRepository,
  type NouvelEleve,
} from '../domain/eleves.types'

export class HttpElevesRepository implements ElevesRepository {
  private readonly http: HttpClient

  constructor(http: HttpClient) {
    this.http = http
  }

  async lister(recherche: string, classeUuid?: string): Promise<Eleve[]> {
    const params = new URLSearchParams({ per_page: '100' })
    if (recherche.trim()) params.set('q', recherche.trim())
    if (classeUuid) params.set('classe', classeUuid)
    return (await this.http.get<{ data: Eleve[] }>(`eleves?${params}`)).data
  }

  async creer(donnees: NouvelEleve): Promise<CreationEleve> {
    try {
      return await this.http.post<CreationEleve>('eleves', donnees)
    } catch (error) {
      if (error instanceof ApiError && error.status === 409) {
        const body = error.body as { existant?: EleveExistant } | undefined
        throw new DoublonDetecte(body?.existant ?? { uuid: '', matricule: '', nom: donnees.nom })
      }
      throw error
    }
  }
}
