<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress core: kotak centang "Notify me of follow-up comments by email".
 * Pengomentar yang mencentangnya akan dikirimi email tiap kali komentarnya
 * (atau turunannya) mendapat balasan yang disetujui. Setiap langganan punya
 * token acak untuk tautan berhenti-langganan tanpa login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('subscribed')->default(false)->after('status');
            $table->uuid('unsubscribe_token')->nullable()->unique()->after('subscribed');
            // Cegah kirim ganda bila satu komentar dapat beberapa balasan
            // dalam rentang singkat — dicatat kapan terakhir diberi tahu.
            $table->timestamp('reply_notified_at')->nullable()->after('unsubscribe_token');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['subscribed', 'unsubscribe_token', 'reply_notified_at']);
        });
    }
};
