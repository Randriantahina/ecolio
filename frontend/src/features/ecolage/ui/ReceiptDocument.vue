<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { formatAr, formatDate } from '@/shared/format'
import type { Recu } from '../domain/ecolage.types'

defineProps<{ recu: Recu }>()
const { t } = useI18n()
</script>

<template>
  <!-- RG-R4: school, number, date, pupil, lines, mode, reference, total, balance, signatory.
       RG-R6: the word "facture" is deliberately absent. -->
  <article
    class="print-area relative overflow-hidden rounded-xl border bg-white p-6 text-slate-900 shadow-sm"
  >
    <div
      v-if="recu.annule"
      class="pointer-events-none absolute inset-0 grid place-items-center"
      aria-hidden="true"
    >
      <span
        class="-rotate-12 rounded-lg border-4 border-red-600/70 px-6 py-2 text-5xl font-black tracking-widest text-red-600/70"
      >
        {{ t('ecolage.receipt.void') }}
      </span>
    </div>

    <header class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
      <div>
        <h2 class="text-lg font-bold">{{ recu.ecole?.nom }}</h2>
        <p v-if="recu.ecole?.adresse" class="text-sm text-slate-600">{{ recu.ecole.adresse }}</p>
        <p v-if="recu.ecole?.telephone" class="text-sm text-slate-600">
          {{ recu.ecole.telephone }}
        </p>
      </div>
      <div class="text-right">
        <p class="text-xs uppercase tracking-wider text-slate-500">{{ recu.mention }}</p>
        <p class="font-mono text-xl font-bold tabular-nums">
          {{ t('ecolage.receipt.number') }} {{ recu.numero }}
        </p>
        <p class="text-sm text-slate-600">{{ formatDate(recu.date_paiement) }}</p>
      </div>
    </header>

    <dl class="grid grid-cols-2 gap-x-6 gap-y-1 py-4 text-sm">
      <dt class="text-slate-500">{{ t('ecolage.receipt.student') }}</dt>
      <dd class="font-medium">{{ recu.eleve.nom }} {{ recu.eleve.prenoms }}</dd>
      <dt class="text-slate-500">{{ t('ecolage.receipt.matricule') }}</dt>
      <dd class="font-mono">{{ recu.eleve.matricule }}</dd>
      <dt v-if="recu.eleve.classe" class="text-slate-500">{{ t('ecolage.receipt.class') }}</dt>
      <dd v-if="recu.eleve.classe">{{ recu.eleve.classe }}</dd>
      <dt class="text-slate-500">{{ t('ecolage.receipt.mode') }}</dt>
      <dd>{{ t(`ecolage.modes.${recu.mode}`) }}</dd>
      <template v-if="recu.reference_externe">
        <dt class="text-slate-500">{{ t('ecolage.receipt.reference') }}</dt>
        <dd class="font-mono">{{ recu.reference_externe }}</dd>
      </template>
    </dl>

    <table class="w-full text-sm">
      <tbody>
        <tr v-for="(ligne, i) in recu.lignes" :key="i" class="border-t border-slate-100">
          <td class="py-2">
            {{ ligne.libelle }}
          </td>
          <td class="py-2 text-right tabular-nums">{{ formatAr(ligne.montant_ar) }}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr class="border-t-2 border-slate-300">
          <th class="py-2 text-left text-base">{{ t('ecolage.receipt.total') }}</th>
          <td class="py-2 text-right text-base font-bold tabular-nums">
            {{ formatAr(recu.total_ar) }}
          </td>
        </tr>
        <tr v-if="recu.reste_a_payer_ar !== null">
          <th class="text-left font-normal text-slate-500">{{ t('ecolage.receipt.balance') }}</th>
          <td class="text-right tabular-nums text-slate-600">
            {{ formatAr(recu.reste_a_payer_ar) }}
          </td>
        </tr>
      </tfoot>
    </table>

    <p
      v-if="recu.annule && recu.motif_annulation"
      class="mt-4 rounded-md bg-red-50 p-2 text-sm text-red-700"
    >
      {{ t('ecolage.receipt.voidReason', { motif: recu.motif_annulation }) }}
    </p>

    <footer class="mt-8 flex justify-end text-sm text-slate-500">
      <div class="w-40 border-t border-slate-300 pt-1 text-center">
        {{ t('ecolage.receipt.signature') }}
      </div>
    </footer>
  </article>
</template>
