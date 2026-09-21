<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Draf kilat
        </x-slot>

        <x-slot name="description">
            Simpan ide berita dengan cepat sebagai draft.
        </x-slot>

        <form wire:submit="save" class="space-y-4">
            {{ $this->form }}

            <x-filament::button type="submit" size="sm">
                Simpan draf
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
