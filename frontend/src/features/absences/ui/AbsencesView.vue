<script setup lang="ts">
import { CalendarCheck, Check, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useRepositories } from '@/app/repositories'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import EmptyState from '@/shared/ui/EmptyState.vue'
import PageHeader from '@/shared/ui/PageHeader.vue'
import { useAbsencesViewModel } from '../absences.viewmodel'

const { t } = useI18n()
const vm = useAbsencesViewModel(useRepositories())
const {
  classes,
  classeUuid,
  date,
  periode,
  eleves,
  chargement,
  absents,
  nombreAbsents,
  modifie,
  enregistre,
  enCours,
  erreur,
} = vm
</script>

<template>
  <PageHeader :title="t('absences.title')" :subtitle="t('absences.subtitle')">
    <template #actions>
      <Select v-model="classeUuid" :disabled="!classes.length">
        <SelectTrigger class="h-11 w-40" :aria-label="t('common.class')"
          ><SelectValue
        /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="c in classes" :key="c.uuid" :value="c.uuid">{{ c.nom }}</SelectItem>
        </SelectContent>
      </Select>
      <Input v-model="date" type="date" :aria-label="t('common.date')" class="h-11 w-44" />
      <Select v-model="periode">
        <SelectTrigger class="h-11 w-36" :aria-label="t('absences.period')"
          ><SelectValue
        /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="p in ['journee', 'matin', 'apres_midi']" :key="p" :value="p">{{
            t(`absences.periods.${p}`)
          }}</SelectItem>
        </SelectContent>
      </Select>
    </template>
  </PageHeader>

  <EmptyState
    v-if="!classes.length"
    :title="t('common.noClassTitle')"
    :body="t('common.noClassBody')"
  >
    <template #icon><CalendarCheck class="size-8" /></template>
  </EmptyState>

  <template v-else>
    <div
      class="sticky top-14 z-10 -mx-4 mb-4 flex flex-wrap items-center justify-between gap-3 border-b bg-background/90 px-4 py-3 backdrop-blur md:static md:mx-0 md:rounded-2xl md:border md:bg-card md:px-4"
    >
      <p class="text-sm font-medium" aria-live="polite">
        {{ t('absences.summary', { n: nombreAbsents, total: eleves.length }) }}
      </p>
      <div class="flex items-center gap-2">
        <Button variant="outline" class="h-11" @click="vm.toutPresents()">{{
          t('absences.allPresent')
        }}</Button>
        <Button class="h-11" :disabled="!modifie || enCours" @click="vm.sauvegarder()">
          {{ enCours ? t('common.saving') : t('common.save') }}
        </Button>
      </div>
    </div>

    <p
      v-if="enregistre"
      role="status"
      class="mb-3 rounded-xl border border-success/30 bg-success-soft p-3 text-sm"
    >
      {{ t('absences.saved') }}
    </p>
    <p
      v-if="erreur"
      role="alert"
      class="mb-3 rounded-xl bg-destructive/10 p-3 text-sm text-destructive"
    >
      {{ erreur }}
    </p>

    <div v-if="chargement" class="space-y-2">
      <Skeleton v-for="n in 8" :key="n" class="h-16 rounded-xl" />
    </div>
    <EmptyState v-else-if="!eleves.length" :title="t('absences.empty')" />

    <ul v-else class="space-y-2">
      <li
        v-for="e in eleves"
        :key="e.uuid"
        class="flex items-center gap-3 rounded-2xl border bg-card p-3 transition-colors"
        :class="absents[e.uuid] ? 'border-destructive/30 bg-destructive/5' : ''"
      >
        <button
          type="button"
          role="switch"
          :aria-checked="!absents[e.uuid]"
          :aria-label="`${e.nom} ${e.prenoms ?? ''} : ${absents[e.uuid] ? t('absences.absent') : t('absences.present')}`"
          class="grid size-12 shrink-0 place-items-center rounded-xl border-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
          :class="
            absents[e.uuid]
              ? 'border-destructive bg-destructive text-white'
              : 'border-success bg-success text-white'
          "
          @click="vm.basculer(e.uuid)"
        >
          <X v-if="absents[e.uuid]" class="size-6" stroke-width="3" aria-hidden="true" />
          <Check v-else class="size-6" stroke-width="3" aria-hidden="true" />
        </button>
        <div class="min-w-0 flex-1">
          <p class="truncate font-medium">{{ e.nom }} {{ e.prenoms }}</p>
          <p class="font-mono text-xs text-muted-foreground">{{ e.matricule }}</p>
        </div>
        <button
          v-if="absents[e.uuid]"
          type="button"
          class="h-10 rounded-lg border px-3 text-sm font-medium transition"
          :class="
            absents[e.uuid]!.justifiee
              ? 'border-primary bg-primary text-primary-foreground'
              : 'bg-card hover:bg-accent'
          "
          :aria-pressed="absents[e.uuid]!.justifiee"
          @click="vm.basculerJustifiee(e.uuid)"
        >
          {{ t('absences.justified') }}
        </button>
        <span v-else class="hidden text-sm text-success sm:inline">{{
          t('absences.present')
        }}</span>
      </li>
    </ul>
  </template>
</template>
