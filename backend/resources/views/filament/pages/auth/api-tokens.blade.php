<div class="space-y-4">
    @php $tokens = $this->getApiTokens(); @endphp

    @if ($tokens->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada token API.</p>
    @else
        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($tokens as $token)
                <li class="flex items-center justify-between gap-x-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $token->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Dibuat {{ $token->created_at->diffForHumans() }}
                            @if ($token->last_used_at)
                                &middot; terakhir dipakai {{ $token->last_used_at->diffForHumans() }}
                            @else
                                &middot; belum pernah dipakai
                            @endif
                        </p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="mountAction('revokeApiToken', { id: {{ $token->id }} })"
                    >
                        Cabut
                    </x-filament::button>
                </li>
            @endforeach
        </ul>
    @endif

    <div>
        {{ $this->createApiTokenAction }}
    </div>
</div>
