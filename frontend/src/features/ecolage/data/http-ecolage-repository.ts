import type { HttpClient } from '@/shared/api/http-client'
import type { EcolageRepository, Encaissement, LigneRegistre, Recu } from '../domain/ecolage.types'

export class HttpEcolageRepository implements EcolageRepository {
  private readonly http: HttpClient

  constructor(http: HttpClient) {
    this.http = http
  }

  async registre(classeUuid: string, periode: string): Promise<LigneRegistre[]> {
    return (
      await this.http.get<{ data: LigneRegistre[] }>(
        `classes/${classeUuid}/registre-ecolage?periode=${periode}`,
      )
    ).data
  }

  async encaisser(encaissement: Encaissement): Promise<Recu> {
    return (await this.http.post<{ paiement: Recu }>('paiements', encaissement)).paiement
  }

  async annuler(paiementUuid: string, motif: string): Promise<Recu> {
    return (
      await this.http.post<{ paiement: Recu }>(`paiements/${paiementUuid}/annulation`, { motif })
    ).paiement
  }

  recuPdf(paiementUuid: string): Promise<Blob> {
    return this.http.blob(`paiements/${paiementUuid}/pdf`)
  }

  async recu(paiementUuid: string): Promise<Recu> {
    return (await this.http.get<{ paiement: Recu }>(`paiements/${paiementUuid}`)).paiement
  }
}
