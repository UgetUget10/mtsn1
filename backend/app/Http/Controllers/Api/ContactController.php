<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Notifications\NewContactMessage;
use App\Support\EditorialStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: field "website" disembunyikan lewat CSS di form. Bot
        // pengisi-otomatis mengisinya; manusia tidak. Balas 201 palsu agar
        // bot mengira berhasil dan tidak mencoba lagi.
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Pesan Anda berhasil dikirim. Terima kasih.'], 201);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $contact = Contact::create($data);

        $staff = EditorialStaff::all();
        if ($staff->isNotEmpty()) {
            Notification::send($staff, new NewContactMessage($contact));
        }

        return response()->json([
            'message' => 'Pesan Anda berhasil dikirim. Terima kasih.',
            'id' => $contact->id,
        ], 201);
    }
}
