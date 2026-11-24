import type { Classe } from '@/features/structure/domain/structure.types'

export interface Eleve {
  uuid: string
  matricule: string
  nom: string
  prenoms: string | null
  sexe: 'F' | 'M' | null
  date_naissance: string | null
  lieu_naissance: string | null
  statut: 'actif' | 'sorti' | 'transfere' | 'abandon' | 'diplome'
  classe: Classe | null
}

export interface NouvelEleve {
  nom: string
  prenoms?: string
  sexe?: 'F' | 'M'
  date_naissance?: string
  classe_uuid?: string
  confirmer_doublon?: boolean
}

export interface CreationEleve {
  eleve: Eleve
  avertissements: { effectif_depasse: boolean }
}

/** RG-E4: the server found a probable duplicate and waits for an explicit confirmation. */
export interface EleveExistant {
  uuid: string
  matricule: string
  nom: string
}

export class DoublonDetecte extends Error {
  readonly existant: EleveExistant

  constructor(existant: EleveExistant) {
    super('doublon')
    this.existant = existant
  }
}

export interface ElevesRepository {
  lister(recherche: string, classeUuid?: string): Promise<Eleve[]>
  creer(donnees: NouvelEleve): Promise<CreationEleve>
}
