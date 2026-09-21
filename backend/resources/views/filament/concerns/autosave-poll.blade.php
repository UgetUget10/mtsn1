{{-- Elemen tak terlihat: memicu HasAutosave::autosave() secara berkala lewat
     wire:poll, tanpa menambah UI apa pun ke halaman edit. --}}
<div wire:poll.{{ $seconds }}s="autosave" class="hidden"></div>
