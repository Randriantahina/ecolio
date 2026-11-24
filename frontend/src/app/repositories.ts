import { inject, type InjectionKey } from 'vue'
import type { AbsencesRepository } from '@/features/absences/domain/absences.types'
import type { EcolageRepository } from '@/features/ecolage/domain/ecolage.types'
import type { ElevesRepository } from '@/features/eleves/domain/eleves.types'
import type { StructureRepository } from '@/features/structure/domain/structure.types'

export interface Repositories {
  structure: StructureRepository
  eleves: ElevesRepository
  ecolage: EcolageRepository
  absences: AbsencesRepository
}

/** One key for every feature repository: views stay testable with fakes, the root picks the adapters. */
export const repositoriesKey: InjectionKey<Repositories> = Symbol('repositories')

export function useRepositories(): Repositories {
  const repositories = inject(repositoriesKey)
  if (!repositories) throw new Error('Repositories are not provided: install them in create-app.ts')
  return repositories
}
