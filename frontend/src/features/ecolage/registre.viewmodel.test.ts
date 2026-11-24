import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { flushPromises, mount } from '@vue/test-utils'
import { defineComponent } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import type { Repositories } from '@/app/repositories'
import type {
  EcheanceRegistre,
  EcolageRepository,
  LigneRegistre,
  Recu,
} from './domain/ecolage.types'
import { useRegistreViewModel } from './registre.viewmodel'

function echeance(over: Partial<EcheanceRegistre>): EcheanceRegistre {
  return {
    uuid: 'e1',
    libelle: 'Écolage Novembre 2026',
    periode: '2026-11',
    type: 'Écolage',
    montant_ar: 50_000,
    remise_ar: 0,
    paye_ar: 0,
    solde_ar: 50_000,
    statut: 'du',
    recu: null,
    paiement_uuid: null,
    ...over,
  }
}

const eleve = { uuid: 'p1', matricule: '2026-0001', nom: 'Rakoto', prenoms: 'Jean' }
const recu = { uuid: 'r1', numero: '2026-0001', total_ar: 50_000 } as Recu

function setup(lignes: LigneRegistre[], ecolage: Partial<EcolageRepository> = {}) {
  const repositories = {
    structure: {
      classes: async () => [{ uuid: 'c1', nom: '6e A', effectif_max: null }],
      anneeCourante: async () => ({
        uuid: 'a',
        libelle: '2026-2027',
        date_debut: '2026-09-01',
        date_fin: '2027-06-30',
        est_courante: true,
      }),
    },
    ecolage: {
      registre: async () => lignes,
      encaisser: vi.fn().mockResolvedValue(recu),
      ...ecolage,
    },
  } as unknown as Pick<Repositories, 'structure' | 'ecolage'>

  let vm!: ReturnType<typeof useRegistreViewModel>
  const Host = defineComponent({
    setup() {
      vm = useRegistreViewModel(repositories, () => true)
      return () => null
    },
  })
  mount(Host, {
    global: {
      plugins: [
        [
          VueQueryPlugin,
          { queryClient: new QueryClient({ defaultOptions: { queries: { retry: false } } }) },
        ],
      ],
    },
  })
  return { vm: () => vm, repositories }
}

describe('registre view model', () => {
  it('sums what is expected, collected and still owed, ignoring cancelled instalments', async () => {
    const { vm } = setup([
      { eleve, echeances: [echeance({ statut: 'paye', paye_ar: 50_000, solde_ar: 0 })] },
      {
        eleve: { ...eleve, uuid: 'p2' },
        echeances: [echeance({ uuid: 'e2', statut: 'partiel', paye_ar: 20_000, solde_ar: 30_000 })],
      },
      { eleve: { ...eleve, uuid: 'p3' }, echeances: [echeance({ uuid: 'e3', statut: 'annule' })] },
    ])
    await flushPromises()

    expect(vm().resume.value).toMatchObject({
      attendu: 100_000,
      encaisse: 70_000,
      reste: 30_000,
      taux: 70,
      impayes: 1,
    })
  })

  it('opens the payment panel with the full balance prefilled, and pays only through the server', async () => {
    const { vm, repositories } = setup([{ eleve, echeances: [echeance({})] }])
    await flushPromises()

    vm().ouvrirPaiement(eleve, echeance({}))
    expect(vm().formulaire.montant).toBe(50_000)

    vm().encaisser()
    await flushPromises()

    const envoye = (repositories.ecolage.encaisser as ReturnType<typeof vi.fn>).mock.calls[0]![0]
    expect(envoye.lignes).toEqual([{ echeance_uuid: 'e1', montant_ar: 50_000 }])
    expect(envoye.client_uuid).toBeTruthy()
    expect(vm().recu.value?.numero).toBe('2026-0001')
    expect(vm().selection.value).toBeNull()
  })

  it('reuses one idempotency key per opened panel so a retry cannot pay twice', async () => {
    const { vm, repositories } = setup([{ eleve, echeances: [echeance({})] }], {
      encaisser: vi.fn().mockRejectedValueOnce(new Error('network')).mockResolvedValue(recu),
    })
    await flushPromises()
    vm().ouvrirPaiement(eleve, echeance({}))

    vm().encaisser()
    await flushPromises()
    vm().encaisser()
    await flushPromises()

    const calls = (repositories.ecolage.encaisser as ReturnType<typeof vi.fn>).mock.calls
    expect(calls[0]![0].client_uuid).toBe(calls[1]![0].client_uuid)
  })
})
