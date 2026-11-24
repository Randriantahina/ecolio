import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, ref, watch } from 'vue'
import type { Repositories } from '@/app/repositories'
import { ValidationError } from '@/shared/api/errors'
import { saveBlob } from '@/shared/download'
import { monthsBetween, todayIso } from '@/shared/format'
import type { EcheanceRegistre, LigneRegistre, ModePaiement, Recu } from './domain/ecolage.types'

export interface CaseSelectionnee {
  eleve: LigneRegistre['eleve']
  echeance: EcheanceRegistre
}

/**
 * The tuition register (spec 0.1, M4). Checking a box never flips local state: it opens the payment
 * panel, and the box changes only once the server answers with a receipt (RG-P1).
 */
export function useRegistreViewModel(
  repositories: Pick<Repositories, 'structure' | 'ecolage'>,
  peutAnnuler: () => boolean,
) {
  const queryClient = useQueryClient()

  const classes = useQuery({
    queryKey: ['classes'],
    queryFn: () => repositories.structure.classes(),
  })
  const annee = useQuery({
    queryKey: ['annee-courante'],
    queryFn: () => repositories.structure.anneeCourante(),
  })

  const classeUuid = ref<string | null>(null)
  const periode = ref<string | null>(null)

  const mois = computed(() =>
    annee.data.value ? monthsBetween(annee.data.value.date_debut, annee.data.value.date_fin) : [],
  )

  // Sensible defaults so the screen shows data at once: first class, and the current month if it is in the year.
  watch(
    () => classes.data.value,
    (liste) => {
      if (liste?.length && classeUuid.value === null) classeUuid.value = liste[0]!.uuid
    },
    { immediate: true },
  )
  watch(
    mois,
    (liste) => {
      if (!liste.length || periode.value !== null) return
      const courant = todayIso().slice(0, 7)
      periode.value = liste.includes(courant) ? courant : liste[0]!
    },
    { immediate: true },
  )

  const registre = useQuery({
    queryKey: ['registre', classeUuid, periode],
    queryFn: () => repositories.ecolage.registre(classeUuid.value!, periode.value!),
    enabled: computed(() => classeUuid.value !== null && periode.value !== null),
  })

  const lignes = computed(() => registre.data.value ?? [])

  /** Header figures: what the month should bring in, what came in, and who is still missing. */
  const resume = computed(() => {
    const actives = lignes.value.flatMap((l) => l.echeances).filter((e) => e.statut !== 'annule')
    const attendu = actives.reduce((s, e) => s + e.montant_ar - e.remise_ar, 0)
    const encaisse = actives.reduce((s, e) => s + e.paye_ar, 0)
    return {
      attendu,
      encaisse,
      reste: attendu - encaisse,
      taux: attendu === 0 ? 0 : Math.round((encaisse / attendu) * 100),
      impayes: lignes.value.filter((l) =>
        l.echeances.some((e) => e.statut === 'du' || e.statut === 'partiel'),
      ).length,
    }
  })

  // --- payment panel -------------------------------------------------------
  const selection = ref<CaseSelectionnee | null>(null)
  const formulaire = reactive({
    montant: 0,
    mode: 'especes' as ModePaiement,
    reference: '',
    date: todayIso(),
  })
  const erreur = ref<string | null>(null)
  let cleIdempotence = crypto.randomUUID()

  function ouvrirPaiement(eleve: LigneRegistre['eleve'], echeance: EcheanceRegistre) {
    selection.value = { eleve, echeance }
    formulaire.montant = echeance.solde_ar
    formulaire.mode = 'especes'
    formulaire.reference = ''
    formulaire.date = todayIso()
    erreur.value = null
    // One key per opened panel: a double tap or a retry after a network cut replays, never pays twice (spec 8.3).
    cleIdempotence = crypto.randomUUID()
  }

  function fermerPaiement() {
    selection.value = null
  }

  const recu = ref<Recu | null>(null)

  const encaisser = useMutation({
    mutationFn: () => {
      const { eleve, echeance } = selection.value!
      return repositories.ecolage.encaisser({
        eleve_uuid: eleve.uuid,
        lignes: [{ echeance_uuid: echeance.uuid, montant_ar: formulaire.montant }],
        mode: formulaire.mode,
        date_paiement: formulaire.date,
        reference_externe: formulaire.reference.trim() || undefined,
        client_uuid: cleIdempotence,
      })
    },
    onSuccess: async (resultat) => {
      selection.value = null
      recu.value = resultat
      await queryClient.invalidateQueries({ queryKey: ['registre'] })
    },
    onError: (e) => {
      erreur.value =
        e instanceof ValidationError
          ? (Object.values(e.errors).flat()[0] ?? e.message)
          : 'Le paiement n’a pas pu être enregistré.'
    },
  })

  // --- receipt and void ----------------------------------------------------
  async function voirRecu(paiementUuid: string) {
    recu.value = await repositories.ecolage.recu(paiementUuid)
  }

  const telechargement = ref(false)

  async function telechargerRecu() {
    if (!recu.value) return
    telechargement.value = true
    try {
      saveBlob(await repositories.ecolage.recuPdf(recu.value.uuid), `recu-${recu.value.numero}.pdf`)
    } finally {
      telechargement.value = false
    }
  }

  function fermerRecu() {
    recu.value = null
  }

  const annulation = reactive({ ouverte: false, motif: '', erreur: null as string | null })

  function demanderAnnulation() {
    annulation.ouverte = true
    annulation.motif = ''
    annulation.erreur = null
  }

  const annuler = useMutation({
    mutationFn: () => repositories.ecolage.annuler(recu.value!.uuid, annulation.motif.trim()),
    onSuccess: async (resultat) => {
      recu.value = resultat
      annulation.ouverte = false
      await queryClient.invalidateQueries({ queryKey: ['registre'] })
    },
    onError: (e) => {
      annulation.erreur =
        e instanceof ValidationError
          ? (Object.values(e.errors).flat()[0] ?? e.message)
          : 'Annulation impossible.'
    },
  })

  return {
    classes: computed(() => classes.data.value ?? []),
    mois,
    classeUuid,
    periode,
    lignes,
    resume,
    chargement: computed(() => registre.isPending.value && registre.fetchStatus.value !== 'idle'),
    selection,
    formulaire,
    erreur,
    ouvrirPaiement,
    fermerPaiement,
    encaisser: () => encaisser.mutate(),
    enCours: computed(() => encaisser.isPending.value),
    recu,
    voirRecu,
    telechargerRecu,
    telechargement,
    fermerRecu,
    peutAnnuler,
    annulation,
    demanderAnnulation,
    annuler: () => annuler.mutate(),
    annulationEnCours: computed(() => annuler.isPending.value),
  }
}
