export interface Niveau {
  uuid: string
  nom: string
  cycle: string
  ordre: number
}

export interface Classe {
  uuid: string
  nom: string
  effectif_max: number | null
  niveau?: Niveau
}

export interface AnneeScolaire {
  uuid: string
  libelle: string
  date_debut: string
  date_fin: string
  est_courante: boolean
}

export interface StructureRepository {
  classes(): Promise<Classe[]>
  anneeCourante(): Promise<AnneeScolaire | null>
}
