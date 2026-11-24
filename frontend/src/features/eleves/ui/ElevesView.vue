<script setup lang="ts">
import { AlertTriangle, GraduationCap, Plus, Search } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useRepositories } from '@/app/repositories'
import { Badge } from '@/components/ui/badge'
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
import { useElevesViewModel } from '../eleves.viewmodel'

const { t } = useI18n()
const vm = useElevesViewModel(useRepositories())
const {
  recherche,
  filtreClasse,
  classes,
  eleves,
  chargement,
  ouvert,
  formulaire,
  erreurs,
  doublon,
  avertissementEffectif,
  dernierCree,
  enCours,
} = vm
</script>

<template>
  <PageHeader :title="t('eleves.title')" :subtitle="t('eleves.count', { n: eleves.length })">
    <template #actions>
      <Button class="h-11" @click="vm.ouvrir()"
        ><Plus class="size-4" aria-hidden="true" />{{ t('eleves.add') }}</Button
      >
    </template>
  </PageHeader>

  <p
    v-if="dernierCree"
    role="status"
    class="mb-4 rounded-xl border border-success/30 bg-success-soft p-3 text-sm"
  >
    {{ t('eleves.created', { nom: dernierCree }) }}
    <span v-if="avertissementEffectif" class="mt-1 flex items-center gap-2 text-warning">
      <AlertTriangle class="size-4" aria-hidden="true" />{{ t('eleves.overCapacity') }}
    </span>
  </p>

  <div class="mb-4 flex flex-col gap-3 sm:flex-row">
    <div class="relative flex-1">
      <Search
        class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
      <Input
        v-model="recherche"
        type="search"
        :placeholder="t('eleves.searchPlaceholder')"
        :aria-label="t('common.search')"
        class="h-11 pl-9"
      />
    </div>
    <Select v-model="filtreClasse">
      <SelectTrigger class="h-11 sm:w-48" :aria-label="t('common.class')"
        ><SelectValue
      /></SelectTrigger>
      <SelectContent>
        <SelectItem value="toutes">{{ t('common.all') }}</SelectItem>
        <SelectItem v-for="c in classes" :key="c.uuid" :value="c.uuid">{{ c.nom }}</SelectItem>
      </SelectContent>
    </Select>
  </div>

  <div v-if="chargement" class="space-y-2">
    <Skeleton v-for="n in 8" :key="n" class="h-16 rounded-xl" />
  </div>

  <EmptyState v-else-if="!eleves.length" :title="t('eleves.empty')">
    <template #icon><GraduationCap class="size-8" /></template>
  </EmptyState>

  <div v-else class="overflow-hidden rounded-2xl border bg-card">
    <table class="w-full text-sm">
      <thead
        class="hidden bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground md:table-header-group"
      >
        <tr>
          <th class="px-4 py-3 font-medium">{{ t('eleves.columns.matricule') }}</th>
          <th class="px-4 py-3 font-medium">{{ t('eleves.columns.name') }}</th>
          <th class="px-4 py-3 font-medium">{{ t('eleves.columns.class') }}</th>
          <th class="px-4 py-3 font-medium">{{ t('eleves.columns.status') }}</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr
          v-for="e in eleves"
          :key="e.uuid"
          class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 md:table-row md:p-0"
        >
          <td class="font-mono text-xs text-muted-foreground md:px-4 md:py-3">{{ e.matricule }}</td>
          <td class="font-medium md:px-4 md:py-3">{{ e.nom }} {{ e.prenoms }}</td>
          <td class="md:px-4 md:py-3">{{ e.classe?.nom ?? t('eleves.noClass') }}</td>
          <td class="md:px-4 md:py-3">
            <Badge :variant="e.statut === 'actif' ? 'secondary' : 'outline'">{{
              t(`eleves.status.${e.statut}`)
            }}</Badge>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <Dialog v-model:open="ouvert">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ t('eleves.form.title') }}</DialogTitle>
        <DialogDescription>{{ t('eleves.form.description') }}</DialogDescription>
      </DialogHeader>

      <form class="space-y-4" @submit.prevent="vm.soumettre(false)">
        <div class="space-y-2">
          <Label for="nom">{{ t('eleves.form.lastName') }}</Label>
          <Input
            id="nom"
            v-model="formulaire.nom"
            required
            class="h-11"
            :aria-invalid="!!erreurs.nom"
          />
          <p v-if="erreurs.nom" role="alert" class="text-sm text-destructive">{{ erreurs.nom }}</p>
        </div>
        <div class="space-y-2">
          <Label for="prenoms">{{ t('eleves.form.firstNames') }}</Label>
          <Input id="prenoms" v-model="formulaire.prenoms" class="h-11" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-2">
            <Label for="sexe">{{ t('eleves.form.sex') }}</Label>
            <Select v-model="formulaire.sexe">
              <SelectTrigger id="sexe" class="h-11"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="F">{{ t('eleves.form.female') }}</SelectItem>
                <SelectItem value="M">{{ t('eleves.form.male') }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="naissance">{{ t('eleves.form.birthDate') }}</Label>
            <Input id="naissance" v-model="formulaire.date_naissance" type="date" class="h-11" />
          </div>
        </div>
        <div class="space-y-2">
          <Label for="classe">{{ t('eleves.form.class') }}</Label>
          <Select v-model="formulaire.classe_uuid">
            <SelectTrigger id="classe" class="h-11"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="c in classes" :key="c.uuid" :value="c.uuid">{{
                c.nom
              }}</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="erreurs.classe_uuid" role="alert" class="text-sm text-destructive">
            {{ erreurs.classe_uuid }}
          </p>
        </div>

        <div
          v-if="doublon"
          role="alert"
          class="rounded-xl border border-warning/40 bg-warning-soft p-3 text-sm"
        >
          <p class="flex items-center gap-2 font-medium">
            <AlertTriangle class="size-4" aria-hidden="true" />{{ t('eleves.duplicate.title') }}
          </p>
          <p class="mt-1">
            {{ t('eleves.duplicate.body', { nom: doublon.nom, matricule: doublon.matricule }) }}
          </p>
          <Button type="button" variant="outline" class="mt-2 h-10" @click="vm.soumettre(true)">{{
            t('eleves.duplicate.confirm')
          }}</Button>
        </div>

        <DialogFooter>
          <Button
            type="submit"
            class="h-12 w-full text-base"
            :disabled="enCours || !formulaire.nom.trim()"
            >{{ t('eleves.form.submit') }}</Button
          >
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
