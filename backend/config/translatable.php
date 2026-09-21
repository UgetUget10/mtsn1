<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Locale yang didukung
    |--------------------------------------------------------------------------
    |
    | Dipakai App\Filament\Concerns\HasTranslatableTabs untuk membangun tab
    | per-bahasa di form Filament, dan oleh frontend Next.js (?locale=) untuk
    | validasi/negosiasi bahasa. 'id' harus selalu ada karena itu locale
    | default APP_LOCALE dan basis data konten yang sudah ada.
    |
    */
    'locales' => [
        'id' => 'Indonesia',
        'en' => 'English',
    ],
];
