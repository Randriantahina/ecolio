<script setup lang="ts">
import { Wallet } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useRepositories } from '@/app/repositories'
import { useSessionStore } from '@/features/auth/session.store'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { formatAr, formatPeriode } from '@/shared/format'
import EmptyState from '@/shared/ui/EmptyState.vue'
import PageHeader from '@/shared/ui/PageHeader.vue'
import { useRegistreViewModel } from '../registre.viewmodel'
import PaiementDialog from './PaiementDialog.vue'
import PaymentBox from './PaymentBox.vue'
import RecuDialog from './RecuDialog.vue'

const { t } = useI18n()
const session = useSessionStore()
const vm = useRegistreViewModel(useRepositories(), () => session.hasRole('directeur'))
const { classes, mois, classeUuid, periode, lignes, resume, chargement } = vm

function ouvrir(
  ligne: (typeof lignes.value)[number],
  echeance: (typeof lignes.value)[number]['echeances'][number],
) {
  // A paid box shows its receipt; anything still owing opens the payment panel (RG-P1: no tick without a payment).
  if (echeance.statut === 'paye' && echeance.paiement_uuid) void vm.voirRecu(echeance.paiement_uuid)
  else vm.ouvrirPaiement(ligne.eleve, echeance)
}
</script>

<template>
  <PageHeader :title="t('ecolage.title')" :subtitle="t('ecolage.subtitle')">
    <template #actions>
      <Select v-model="classeUuid" :disabled="!classes.length">
        <SelectTrigger class="h-11 w-44" :aria-label="t('common.class')"
          ><SelectValue :placeholder="t('common.class')"
        /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="c in classes" :key="c.uuid" :value="c.uuid">{{ c.nom }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="periode" :disabled="!mois.length">
        <SelectTrigger class="h-11 w-48" :aria-label="t('common.month')"
          ><SelectValue :placeholder="t('common.month')"
        /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="m in mois" :key="m" :value="m">{{ formatPeriode(m) }}</SelectItem>
        </SelectContent>
      </Select>
    </template>
  </PageHeader>

  <EmptyState
    v-if="!classes.length"
    :title="t('common.noClassTitle')"
    :body="t('common.noClassBody')"
  >
    <template #icon><Wallet class="size-8" /></template>
  </EmptyState>

  <template v-else>
    <section class="mb-6 grid gap-3 sm:grid-cols-3" aria-live="polite">
      <div class="rounded-2xl border bg-card p-4">
        <p class="text-sm text-muted-foreground">{{ t('ecolage.expected') }}</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ formatAr(resume.attendu) }}</p>
      </div>
      <div class="rounded-2xl border bg-card p-4">
        <p class="text-sm text-muted-foreground">{{ t('ecolage.collected') }}</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-success">
          {{ formatAr(resume.encaisse) }}
        </p>
        <div
          class="mt-3 h-2 overflow-hidden rounded-full bg-muted"
          role="progressbar"
          :aria-valuenow="resume.taux"
          aria-valuemin="0"
          aria-valuemax="100"
        >
          <div
            class="h-full rounded-full bg-success transition-all duration-500"
            :style="{ width: `${resume.taux}%` }"
          />
        </div>
        <p class="mt-1.5 text-xs text-muted-foreground">
          {{ t('ecolage.rate', { n: resume.taux }) }}
        </p>
      </div>
      <div class="rounded-2xl border bg-card p-4">
        <p class="text-sm text-muted-foreground">{{ t('ecolage.remaining') }}</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ formatAr(resume.reste) }}</p>
        <p class="mt-3 text-xs text-muted-foreground">
          {{ t('ecolage.unpaid', { n: resume.impayes }) }}
        </p>
      </div>
    </section>

    <div v-if="chargement" class="space-y-3">
      <Skeleton v-for="n in 6" :key="n" class="h-20 rounded-2xl" />
    </div>

    <EmptyState v-else-if="!lignes.length" :title="t('ecolage.empty')" />

    <ul v-else class="space-y-3">
      <li
        v-for="ligne in lignes"
        :key="ligne.eleve.uuid"
        class="flex flex-col gap-3 rounded-2xl border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex min-w-0 items-center gap-3">
          <span
            class="grid size-11 shrink-0 place-items-center rounded-full bg-accent text-sm font-semibold text-accent-foreground"
            aria-hidden="true"
          >
            {{ (ligne.eleve.nom[0] ?? '') + (ligne.eleve.prenoms?.[0] ?? '') }}
          </span>
          <div class="min-w-0">
            <p class="truncate font-medium">{{ ligne.eleve.nom }} {{ ligne.eleve.prenoms }}</p>
            <p class="font-mono text-xs text-muted-foreground">{{ ligne.eleve.matricule }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <PaymentBox
            v-for="e in ligne.echeances"
            :key="e.uuid"
            :echeance="e"
            @select="ouvrir(ligne, e)"
          />
        </div>
      </li>
    </ul>
  </template>

  <PaiementDialog :vm="vm" />
  <RecuDialog :vm="vm" />
</template>
