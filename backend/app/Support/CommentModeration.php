<?php

namespace App\Support;

use App\Models\Comment;

/**
 * Filter komentar ala WordPress → Settings → Discussion:
 *
 *  - "Comment must be manually approved" (auto_approve) → sudah di DiscussionSettings.
 *  - "Hold a comment in the queue if it contains N or more links" → holdForLinks().
 *  - "Comment Moderation" keys (satu per baris; kata/nama/URL/email/IP) →
 *    kena → status `pending` (tahan di antrean).
 *  - "Disallowed Comment Keys" → kena → status `spam` (WP: langsung ke trash;
 *    kita pakai `spam` supaya tetap terlihat & bisa dipulihkan di panel).
 *
 * Pencocokan meniru `wp_check_comment_disallowed_list()`: substring
 * case-insensitive terhadap gabungan nama + email + URL + IP + isi komentar.
 * Baris berisi frasa (ada spasi) dicocokkan apa adanya; kata tunggal dicocokkan
 * sebagai substring juga (sama seperti WP — bukan whole-word).
 */
class CommentModeration
{
    /**
     * Tentukan status awal komentar publik baru.
     *
     * @param  array{author_name?:string,author_email?:string,author_url?:string,author_ip?:string,body?:string}  $fields
     */
    public static function initialStatus(array $fields): string
    {
        $haystack = self::haystack($fields);

        if (self::matchesAny($haystack, DiscussionSettings::disallowedKeys())) {
            return Comment::STATUS_SPAM;
        }

        if (self::matchesAny($haystack, DiscussionSettings::moderationKeys())) {
            return Comment::STATUS_PENDING;
        }

        if (self::holdForLinks($fields['body'] ?? '')) {
            return Comment::STATUS_PENDING;
        }

        return DiscussionSettings::autoApprove()
            ? Comment::STATUS_APPROVED
            : Comment::STATUS_PENDING;
    }

    /** wp: comment_max_links — tahan bila jumlah tautan >= ambang (0 = mati). */
    public static function holdForLinks(string $body): bool
    {
        $max = DiscussionSettings::maxLinks();
        if ($max <= 0) {
            return false;
        }

        // Hitung href="..." + URL telanjang (http/https/www), seperti WP.
        $count = preg_match_all('#(?:https?://|www\.)[^\s"\'<>()]+#i', $body);

        return $count >= $max;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private static function matchesAny(string $haystack, array $keys): bool
    {
        foreach ($keys as $key) {
            $key = trim($key);
            if ($key !== '' && str_contains($haystack, mb_strtolower($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function haystack(array $fields): string
    {
        return mb_strtolower(implode("\n", array_filter([
            $fields['author_name'] ?? '',
            $fields['author_email'] ?? '',
            $fields['author_url'] ?? '',
            $fields['author_ip'] ?? '',
            $fields['body'] ?? '',
        ])));
    }
}
