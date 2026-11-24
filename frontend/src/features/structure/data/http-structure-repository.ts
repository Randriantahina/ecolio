import type { HttpClient } from '@/shared/api/http-client'
import type { AnneeScolaire, Classe, StructureRepository } from '../domain/structure.types'

export class HttpStructureRepository implements StructureRepository {
  private readonly http: HttpClient

  constructor(http: HttpClient) {
    this.http = http
  }

  async classes(): Promise<Classe[]> {
    return (await this.http.get<{ data: Classe[] }>('classes')).data
  }

  async anneeCourante(): Promise<AnneeScolaire | null> {
    const { data } = await this.http.get<{ data: AnneeScolaire[] }>('annees-scolaires')
    return data.find((a) => a.est_courante) ?? null
  }
}
