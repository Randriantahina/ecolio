import type { HttpClient } from '@/shared/api/http-client'
import type { AbsenceSaisie, AbsencesRepository, PeriodeAbsence } from '../domain/absences.types'

export class HttpAbsencesRepository implements AbsencesRepository {
  private readonly http: HttpClient

  constructor(http: HttpClient) {
    this.http = http
  }

  async du(classeUuid: string, date: string, periode: PeriodeAbsence): Promise<AbsenceSaisie[]> {
    return (
      await this.http.get<{ data: AbsenceSaisie[] }>(
        `classes/${classeUuid}/absences?date=${date}&periode=${periode}`,
      )
    ).data
  }

  async saisir(
    classeUuid: string,
    date: string,
    periode: PeriodeAbsence,
    absents: AbsenceSaisie[],
  ): Promise<AbsenceSaisie[]> {
    const body = {
      date,
      periode,
      absents: absents.map((a) => ({
        eleve_uuid: a.eleve_uuid,
        justifiee: a.justifiee,
        motif: a.motif,
      })),
    }
    return (await this.http.put<{ data: AbsenceSaisie[] }>(`classes/${classeUuid}/absences`, body))
      .data
  }
}
