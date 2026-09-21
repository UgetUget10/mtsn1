<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mulai dari sini
        </x-slot>

        <x-slot name="description">
            Beberapa langkah dasar sebelum menerbitkan artikel pertama Anda.
        </x-slot>

        <ul class="space-y-2">
            @foreach ($this->getSteps() as $step)
                <li class="flex items-center gap-x-3">
                    @if ($step['done'])
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 shrink-0 text-success-500" />
                        <span class="text-sm text-gray-500 line-through dark:text-gray-400">{{ $step['label'] }}</span>
                    @else
                        <x-filament::icon icon="heroicon-o-minus-circle" class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" />
                        <a href="{{ $step['url'] }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                            {{ $step['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
