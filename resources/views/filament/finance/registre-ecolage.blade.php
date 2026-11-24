<x-filament-panels::page>
    <x-filament::tabs label="Registre">
        <x-filament::tabs.item :active="! $this->enInscriptions()" wire:click="$set('activeTab', 'ecolages')" icon="heroicon-o-calendar-days">
            Écolages
        </x-filament::tabs.item>
        <x-filament::tabs.item :active="$this->enInscriptions()" wire:click="$set('activeTab', 'inscriptions')" icon="heroicon-o-identification">
            Inscriptions
        </x-filament::tabs.item>
    </x-filament::tabs>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        @if ($this->enInscriptions())
            Frais payés une seule fois par année (types de frais « Unique » ou « Annuel » : inscription, assurance…). Une ligne par élève, sans mois.
        @else
            Frais récurrents (types « Mensuel » ou « Trimestriel » : écolage…). Choisissez la classe et le mois : une ligne par élève.
        @endif
        Les lignes viennent des <strong>tarifs</strong> (menu Finance) et sont créées toutes seules à l'inscription de l'élève.
    </p>

    {{ $this->table }}
</x-filament-panels::page>
