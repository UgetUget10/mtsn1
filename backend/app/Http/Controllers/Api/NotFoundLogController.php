<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use Illuminate\Http\Request;

/**
 * Terima laporan 404 dari frontend (proxy.ts memanggil ini fire-and-forget
 * setiap kali memancarkan 404 sungguhan). Meniru tab "404s" plugin Redirection.
 */
class NotFoundLogController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
        ]);

        NotFoundLog::record(
            $data['path'],
            $data['referrer'] ?? $request->headers->get('referer'),
            (string) $request->userAgent(),
        );

        return response()->noContent();
    }
}
