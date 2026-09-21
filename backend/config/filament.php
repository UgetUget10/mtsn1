<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Semua unggahan berkas dari panel admin (logo, cover berita, foto guru,
    | galeri, dokumen, dsb.) disimpan ke disk "public" agar bisa diakses lewat
    | URL /storage. Disk aplikasi tetap "local" (privat).
    |
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

];
