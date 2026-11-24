import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { refDebounced } from '@vueuse/core'
import { computed, reactive, ref } from 'vue'
import type { Repositories } from '@/app/repositories'
import { ValidationError } from '@/shared/api/errors'
import { DoublonDetecte, type EleveExistant } from './domain/eleves.types'

export function useElevesViewModel(repositories: Pick<Repositories, 'eleves' | 'structure'>) {
  const queryClient = useQueryClient()

  const recherche = ref('')
  // Instant search (US4.4) without hammering the API on every keystroke.
  const rechercheLissee = refDebounced(recherche, 250)
  const filtreClasse = ref<string>('toutes')

  const classes = useQuery({
    queryKey: ['classes'],
    queryFn: () => repositories.structure.classes(),
  })
  const eleves = useQuery({
    queryKey: ['eleves', rechercheLissee, filtreClasse],
    queryFn: () =>
      repositories.eleves.lister(
        rechercheLissee.value,
        filtreClasse.value === 'toutes' ? undefined : filtreClasse.value,
      ),
  })

  // --- creation ------------------------------------------------------------
  const ouvert = ref(false)
  const formulaire = reactive({
    nom: '',
    prenoms: '',
    sexe: '' as '' | 'F' | 'M',
    date_naissance: '',
    classe_uuid: '',
  })
  const erreurs = ref<Record<string, string>>({})
  const doublon = ref<EleveExistant | null>(null)
  const avertissementEffectif = ref(false)
  const dernierCree = ref<string | null>(null)

  function ouvrir() {
    Object.assign(formulaire, {
      nom: '',
      prenoms: '',
      sexe: '',
      date_naissance: '',
      classe_uuid: '',
    })
    erreurs.value = {}
    doublon.value = null
    avertissementEffectif.value = false
    ouvert.value = true
  }

  const creer = useMutation({
    mutationFn: (confirmer: boolean) =>
      repositories.eleves.creer({
        nom: formulaire.nom.trim(),
        prenoms: formulaire.prenoms.trim() || undefined,
        sexe: formulaire.sexe || undefined,
        date_naissance: formulaire.date_naissance || undefined,
        classe_uuid: formulaire.classe_uuid || undefined,
        confirmer_doublon: confirmer || undefined,
      }),
    onSuccess: async (resultat) => {
      avertissementEffectif.value = resultat.avertissements.effectif_depasse
      dernierCree.value = `${resultat.eleve.matricule} · ${resultat.eleve.nom}`
      ouvert.value = false
      await queryClient.invalidateQueries({ queryKey: ['eleves'] })
    },
    onError: (e) => {
      if (e instanceof DoublonDetecte) {
        doublon.value = e.existant
        return
      }
      if (e instanceof ValidationError) {
        erreurs.value = Object.fromEntries(
          Object.entries(e.errors).map(([champ, messages]) => [champ, messages[0] ?? '']),
        )
        return
      }
      erreurs.value = { nom: 'Impossible d’enregistrer l’élève.' }
    },
  })

  function soumettre(confirmerDoublon = false) {
    erreurs.value = {}
    if (!confirmerDoublon) doublon.value = null
    creer.mutate(confirmerDoublon)
  }

  return {
    recherche,
    filtreClasse,
    classes: computed(() => classes.data.value ?? []),
    eleves: computed(() => eleves.data.value ?? []),
    chargement: computed(() => eleves.isPending.value),
    ouvert,
    formulaire,
    erreurs,
    doublon,
    avertissementEffectif,
    dernierCree,
    ouvrir,
    soumettre,
    enCours: computed(() => creer.isPending.value),
  }
}
