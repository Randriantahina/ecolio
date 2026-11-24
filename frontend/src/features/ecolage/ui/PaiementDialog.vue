<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { formatAr } from '@/shared/format'
import { MODES_PAIEMENT } from '../domain/ecolage.types'
import type { useRegistreViewModel } from '../registre.viewmodel'

const props = defineProps<{ vm: ReturnType<typeof useRegistreViewModel> }>()
const { t } = useI18n()
// Local handle on the ViewModel's reactive form: editing it is the view's job, editing the prop itself is not.
const formulaire = props.vm.formulaire

const ouvert = computed({
  get: () => props.vm.selection.value !== null,
  set: (valeur) => {
    if (!valeur) props.vm.fermerPaiement()
  },
})

const mode = computed(() => MODES_PAIEMENT.find((m) => m.valeur === props.vm.formulaire.mode))
const solde = computed(() => props.vm.selection.value?.echeance.solde_ar ?? 0)
const valide = computed(
  () => props.vm.formulaire.montant > 0 && props.vm.formulaire.montant <= solde.value,
)
</script>

<template>
  <Dialog v-model:open="ouvert">
    <DialogContent v-if="vm.selection.value" class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle
          >{{ t('ecolage.pay.title') }} · {{ vm.selection.value.echeance.libelle }}</DialogTitle
        >
        <DialogDescription>
          {{ vm.selection.value.eleve.nom }} {{ vm.selection.value.eleve.prenoms }} ·
          <span class="font-mono">{{ vm.selection.value.eleve.matricule }}</span>
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-5" @submit.prevent="vm.encaisser()">
        <div class="space-y-2">
          <Label for="montant">{{ t('ecolage.pay.amount') }}</Label>
          <div class="flex gap-2">
            <Input
              id="montant"
              v-model.number="formulaire.montant"
              type="number"
              inputmode="numeric"
              min="1"
              :max="solde"
              step="1"
              class="h-12 text-lg font-semibold tabular-nums"
              autofocus
            />
            <Button
              type="button"
              variant="outline"
              class="h-12"
              @click="formulaire.montant = solde"
            >
              {{ t('ecolage.pay.full') }}
            </Button>
            <Button
              type="button"
              variant="outline"
              class="h-12"
              @click="formulaire.montant = Math.floor(solde / 2)"
            >
              {{ t('ecolage.pay.half') }}
            </Button>
          </div>
          <p class="text-sm text-muted-foreground">
            {{ t('ecolage.pay.remaining', { montant: formatAr(solde) }) }}
          </p>
        </div>

        <fieldset class="space-y-2">
          <legend class="text-sm font-medium">{{ t('ecolage.pay.mode') }}</legend>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" role="radiogroup">
            <button
              v-for="m in MODES_PAIEMENT"
              :key="m.valeur"
              type="button"
              role="radio"
              :aria-checked="formulaire.mode === m.valeur"
              class="h-11 rounded-lg border px-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-ring"
              :class="
                formulaire.mode === m.valeur
                  ? 'border-primary bg-primary text-primary-foreground'
                  : 'bg-card hover:bg-accent'
              "
              @click="formulaire.mode = m.valeur"
            >
              {{ t(`ecolage.modes.${m.valeur}`) }}
            </button>
          </div>
        </fieldset>

        <div v-if="mode?.reference" class="space-y-2">
          <Label for="reference">{{ t('ecolage.pay.reference') }}</Label>
          <Input
            id="reference"
            v-model="formulaire.reference"
            :placeholder="t('ecolage.pay.referenceHint')"
            class="h-11"
          />
        </div>

        <div class="space-y-2">
          <Label for="date-paiement">{{ t('ecolage.pay.date') }}</Label>
          <Input id="date-paiement" v-model="formulaire.date" type="date" class="h-11" />
        </div>

        <p
          v-if="vm.erreur.value"
          role="alert"
          class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive"
        >
          {{ vm.erreur.value }}
        </p>

        <DialogFooter>
          <Button
            type="submit"
            class="h-12 w-full text-base"
            :disabled="!valide || vm.enCours.value"
          >
            {{
              vm.enCours.value
                ? t('ecolage.pay.submitting')
                : t('ecolage.pay.submit', { montant: formatAr(formulaire.montant || 0) })
            }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
