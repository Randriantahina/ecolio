import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, ref, watch } from 'vue'
import type { Repositories } from '@/app/repositories'
import { todayIso } from '@/shared/format'
import type { PeriodeAbsence } from './domain/absences.types'

/** "Everyone present, then untick the absentees" (spec 0.1): the screen tracks absentees only. */
export function useAbsencesViewModel(
  repositories: Pick<Repositories, 'structure' | 'eleves' | 'absences'>,
) {
  const queryClient = useQueryClient()

  const classes = useQuery({
    queryKey: ['classes'],
    queryFn: () => repositories.structure.classes(),
  })
  const classeUuid = ref<string | null>(null)
  const date = ref(todayIso())
  const periode = ref<PeriodeAbsence>('journee')

  watch(
    () => classes.data.value,
    (liste) => {
      if (liste?.length && classeUuid.value === null) classeUuid.value = liste[0]!.uuid
    },
    { immediate: true },
  )

  const actif = computed(() => classeUuid.value !== null)

  const eleves = useQuery({
    queryKey: ['eleves-classe', classeUuid],
    queryFn: () => repositories.eleves.lister('', classeUuid.value!),
    enabled: actif,
  })
  const enregistrees = useQuery({
    queryKey: ['absences', classeUuid, date, periode],
    queryFn: () => repositories.absences.du(classeUuid.value!, date.value, periode.value),
    enabled: actif,
  })

  /** uuid -> justified?; presence in the map means absent. */
  const absents = ref<Record<string, { justifiee: boolean; motif: string | null }>>({})
  const modifie = ref(false)
  const enregistre = ref(false)

  watch(
    () => enregistrees.data.value,
    (liste) => {
      absents.value = Object.fromEntries(
        (liste ?? []).map((a) => [a.eleve_uuid, { justifiee: a.justifiee, motif: a.motif }]),
      )
      modifie.value = false
    },
    { immediate: true },
  )

  function basculer(uuid: string) {
    const suivant = { ...absents.value }
    if (suivant[uuid]) delete suivant[uuid]
    else suivant[uuid] = { justifiee: false, motif: null }
    absents.value = suivant
    modifie.value = true
    enregistre.value = false
  }

  function basculerJustifiee(uuid: string) {
    const courant = absents.value[uuid]
    if (!courant) return
    absents.value = { ...absents.value, [uuid]: { ...courant, justifiee: !courant.justifiee } }
    modifie.value = true
    enregistre.value = false
  }

  function toutPresents() {
    absents.value = {}
    modifie.value = true
    enregistre.value = false
  }

  const sauvegarder = useMutation({
    mutationFn: () =>
      repositories.absences.saisir(
        classeUuid.value!,
        date.value,
        periode.value,
        Object.entries(absents.value).map(([eleve_uuid, a]) => ({ eleve_uuid, ...a })),
      ),
    onSuccess: async () => {
      modifie.value = false
      enregistre.value = true
      await queryClient.invalidateQueries({ queryKey: ['absences'] })
    },
  })

  const nombreAbsents = computed(() => Object.keys(absents.value).length)

  return {
    classes: computed(() => classes.data.value ?? []),
    classeUuid,
    date,
    periode,
    eleves: computed(() => eleves.data.value ?? []),
    chargement: computed(() => eleves.isPending.value && actif.value),
    absents,
    nombreAbsents,
    modifie,
    enregistre,
    basculer,
    basculerJustifiee,
    toutPresents,
    sauvegarder: () => sauvegarder.mutate(),
    enCours: computed(() => sauvegarder.isPending.value),
    erreur: computed(() =>
      sauvegarder.isError.value ? 'Enregistrement impossible. Vérifiez la connexion.' : null,
    ),
  }
}
