<script setup lang="ts">
import { Download, Undo2 } from 'lucide-vue-next'
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
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import type { useRegistreViewModel } from '../registre.viewmodel'
import ReceiptDocument from './ReceiptDocument.vue'

const props = defineProps<{ vm: ReturnType<typeof useRegistreViewModel> }>()
const { t } = useI18n()
const annulation = props.vm.annulation

const ouvert = computed({
  get: () => props.vm.recu.value !== null,
  set: (valeur) => {
    if (!valeur) props.vm.fermerRecu()
  },
})

const motifValide = computed(() => props.vm.annulation.motif.trim().length >= 3)
</script>

<template>
  <Dialog v-model:open="ouvert">
    <DialogContent v-if="vm.recu.value" class="max-h-[92svh] overflow-y-auto sm:max-w-xl">
      <DialogHeader class="no-print">
        <DialogTitle>{{ t('ecolage.receipt.title') }}</DialogTitle>
        <DialogDescription class="sr-only">{{ vm.recu.value.numero }}</DialogDescription>
      </DialogHeader>

      <ReceiptDocument :recu="vm.recu.value" />

      <section
        v-if="annulation.ouverte"
        class="no-print space-y-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4"
      >
        <h3 class="font-semibold">{{ t('ecolage.receipt.voidTitle') }}</h3>
        <p class="text-sm text-muted-foreground">{{ t('ecolage.receipt.voidBody') }}</p>
        <div class="space-y-2">
          <Label for="motif">{{ t('ecolage.receipt.voidReasonLabel') }}</Label>
          <Textarea id="motif" v-model="annulation.motif" rows="2" />
        </div>
        <p v-if="annulation.erreur" role="alert" class="text-sm text-destructive">
          {{ annulation.erreur }}
        </p>
        <div class="flex gap-2">
          <Button variant="outline" class="h-11" @click="annulation.ouverte = false">{{
            t('common.cancel')
          }}</Button>
          <Button
            variant="destructive"
            class="h-11 flex-1"
            :disabled="!motifValide || vm.annulationEnCours.value"
            @click="vm.annuler()"
          >
            {{ t('ecolage.receipt.voidConfirm') }}
          </Button>
        </div>
      </section>

      <DialogFooter v-else class="no-print gap-2 sm:justify-between">
        <Button
          v-if="!vm.recu.value.annule && vm.peutAnnuler()"
          variant="ghost"
          class="h-11 text-destructive hover:text-destructive"
          @click="vm.demanderAnnulation()"
        >
          <Undo2 class="size-4" aria-hidden="true" />
          {{ t('ecolage.receipt.voidAction') }}
        </Button>
        <p v-else-if="!vm.recu.value.annule" class="self-center text-xs text-muted-foreground">
          {{ t('ecolage.receipt.voidOnlyDirector') }}
        </p>
        <span v-else />
        <Button class="h-11" @click="vm.telechargerRecu()">
          <Download class="size-4" aria-hidden="true" />
          {{ t('common.download') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
