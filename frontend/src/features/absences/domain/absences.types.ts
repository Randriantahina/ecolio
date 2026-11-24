export interface AbsenceSaisie {
  eleve_uuid: string
  matricule?: string
  justifiee: boolean
  motif: string | null
}

export type PeriodeAbsence = 'journee' | 'matin' | 'apres_midi'

export interface AbsencesRepository {
  du(classeUuid: string, date: string, periode: PeriodeAbsence): Promise<AbsenceSaisie[]>
  saisir(
    classeUuid: string,
    date: string,
    periode: PeriodeAbsence,
    absents: AbsenceSaisie[],
  ): Promise<AbsenceSaisie[]>
}
