<x-filament-panels::page>
    <div class="space-y-4">
        @foreach ($this->getChecks() as $check)
            <div class="flex items-center justify-between gap-x-4 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center gap-x-3">
                    <span @class([
                        'h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-success-500' => $check['status'] === 'good',
                        'bg-warning-500' => $check['status'] === 'warning',
                        'bg-danger-500' => $check['status'] === 'critical',
                    ])></span>
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $check['label'] }}</span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $check['value'] }}</span>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
