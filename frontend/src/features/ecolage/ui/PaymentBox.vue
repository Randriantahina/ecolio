<script setup lang="ts">
import { Ban, Check, Circle, CircleDot } from 'lucide-vue-next'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatAr } from '@/shared/format'
import type { EcheanceRegistre } from '../domain/ecolage.types'

const props = defineProps<{ echeance: EcheanceRegistre }>()
defineEmits<{ select: [] }>()

const { t } = useI18n()

const net = computed(() => props.echeance.montant_ar - props.echeance.remise_ar)
const progression = computed(() =>
  net.value === 0 ? 0 : Math.round((props.echeance.paye_ar / net.value) * 100),
)

const detail = computed(() => {
  const e = props.echeance
  if (e.statut === 'paye') return t('ecolage.box.paid', { numero: e.recu ?? '' })
  if (e.statut === 'partiel') return t('ecolage.box.due', { montant: formatAr(e.solde_ar) })
  if (e.statut === 'annule') return t('ecolage.status.annule')
  return formatAr(e.solde_ar)
})
</script>

<template>
  <!-- A real <button>: the box is the primary action of the app and must work by keyboard and touch. -->
  <button
    type="button"
    :disabled="echeance.statut === 'annule'"
    class="group relative flex min-h-14 min-w-44 items-center gap-3 overflow-hidden rounded-xl border px-3 py-2 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-60"
    :class="{
      'border-success/30 bg-success-soft': echeance.statut === 'paye',
      'border-warning/40 bg-warning-soft': echeance.statut === 'partiel',
      'border-dashed bg-card hover:border-primary hover:bg-accent': echeance.statut === 'du',
      'bg-muted': echeance.statut === 'annule',
    }"
    :aria-label="`${echeance.libelle} : ${t(`ecolage.status.${echeance.statut}`)}, ${detail}`"
    @click="$emit('select')"
  >
    <span
      class="grid size-8 shrink-0 place-items-center rounded-full"
      :class="{
        'bg-success text-white': echeance.statut === 'paye',
        'bg-warning text-white': echeance.statut === 'partiel',
        'text-muted-foreground group-hover:text-primary': echeance.statut === 'du',
        'text-muted-foreground': echeance.statut === 'annule',
      }"
      aria-hidden="true"
    >
      <Check v-if="echeance.statut === 'paye'" class="size-5" stroke-width="3" />
      <CircleDot v-else-if="echeance.statut === 'partiel'" class="size-5" />
      <Ban v-else-if="echeance.statut === 'annule'" class="size-5" />
      <Circle v-else class="size-6" />
    </span>

    <span class="min-w-0 flex-1">
      <span
        class="block truncate text-sm font-medium"
        :class="{ 'line-through': echeance.statut === 'annule' }"
      >
        {{ echeance.type }}
      </span>
      <span class="block truncate text-xs text-muted-foreground">{{ detail }}</span>
    </span>

    <span
      v-if="echeance.statut === 'partiel'"
      class="absolute inset-x-0 bottom-0 h-1 bg-warning/20"
      aria-hidden="true"
    >
      <span class="block h-full bg-warning" :style="{ width: `${progression}%` }" />
    </span>
  </button>
</template>
