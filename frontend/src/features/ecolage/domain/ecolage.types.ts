export type StatutEcheance = 'du' | 'partiel' | 'paye' | 'annule'
export type ModePaiement =
  'especes' | 'mvola' | 'orange_money' | 'airtel_money' | 'virement' | 'cheque'

export interface EcheanceRegistre {
  uuid: string
  libelle: string
  periode: string | null
  type: string
  montant_ar: number
  remise_ar: number
  paye_ar: number
  solde_ar: number
  statut: StatutEcheance
  recu: string | null
  paiement_uuid: string | null
}

export interface LigneRegistre {
  eleve: { uuid: string; matricule: string; nom: string; prenoms: string | null }
  echeances: EcheanceRegistre[]
}

export interface Recu {
  uuid: string
  numero: string | null
  statut: 'valide' | 'annule'
  annule: boolean
  motif_annulation: string | null
  date_paiement: string
  mode: ModePaiement
  reference_externe: string | null
  total_ar: number
  reste_a_payer_ar: number | null
  eleve: {
    uuid: string
    matricule: string
    nom: string
    prenoms: string | null
    classe: string | null
  }
  ecole: { nom: string; adresse: string | null; telephone: string | null } | null
  lignes: { libelle: string; periode: string | null; montant_ar: number }[]
  mention: string
}

export interface Encaissement {
  eleve_uuid: string
  lignes: { echeance_uuid: string; montant_ar: number }[]
  mode: ModePaiement
  date_paiement?: string
  reference_externe?: string
  /** Idempotency key (spec 8.3): retrying the same tap never pays twice. */
  client_uuid: string
}

export interface EcolageRepository {
  registre(classeUuid: string, periode: string): Promise<LigneRegistre[]>
  encaisser(encaissement: Encaissement): Promise<Recu>
  annuler(paiementUuid: string, motif: string): Promise<Recu>
  recu(paiementUuid: string): Promise<Recu>
  recuPdf(paiementUuid: string): Promise<Blob>
}

export const MODES_PAIEMENT: { valeur: ModePaiement; label: string; reference: boolean }[] = [
  { valeur: 'especes', label: 'Espèces', reference: false },
  { valeur: 'mvola', label: 'MVola', reference: true },
  { valeur: 'orange_money', label: 'Orange Money', reference: true },
  { valeur: 'airtel_money', label: 'Airtel Money', reference: true },
  { valeur: 'virement', label: 'Virement', reference: true },
  { valeur: 'cheque', label: 'Chèque', reference: true },
]
