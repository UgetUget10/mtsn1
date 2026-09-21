<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Opsi diskusi/komentar ala WordPress (Settings → Discussion).
 *
 * Sumber nilai berlapis: baris `Setting` (diedit admin lewat panel) menang;
 * bila belum pernah disimpan, jatuh ke `config('editorial.comments.*')`
 * (yang sendiri berasal dari env). Jadi env = default pabrik, panel = override.
 */
class DiscussionSettings
{
    public const KEY_ENABLED = 'comments_enabled';

    public const KEY_AUTO_APPROVE = 'comments_auto_approve';

    public const KEY_CLOSE_AFTER_DAYS = 'comments_close_after_days';

    public const KEY_MAX_DEPTH = 'comments_max_depth';

    public const KEY_REQUIRE_EMAIL = 'comments_require_email';

    public const KEY_SUBSCRIPTIONS = 'comments_subscriptions_enabled';

    public const KEY_MAX_LINKS = 'comments_max_links';

    public const KEY_MODERATION_KEYS = 'comments_moderation_keys';

    public const KEY_DISALLOWED_KEYS = 'comments_disallowed_keys';

    public static function enabled(): bool
    {
        return self::boolSetting(self::KEY_ENABLED, (bool) config('editorial.comments.enabled', true));
    }

    public static function autoApprove(): bool
    {
        return self::boolSetting(self::KEY_AUTO_APPROVE, (bool) config('editorial.comments.auto_approve', false));
    }

    public static function requireEmail(): bool
    {
        return self::boolSetting(self::KEY_REQUIRE_EMAIL, true);
    }

    /** wp: tawarkan checkbox "Notify me of follow-up comments by email". */
    public static function subscriptionsEnabled(): bool
    {
        return self::boolSetting(self::KEY_SUBSCRIPTIONS, true);
    }

    public static function closeAfterDays(): int
    {
        $v = Setting::get(self::KEY_CLOSE_AFTER_DAYS);

        return $v === null || $v === ''
            ? (int) config('editorial.comments.close_after_days', 0)
            : max(0, (int) $v);
    }

    public static function maxDepth(): int
    {
        $v = Setting::get(self::KEY_MAX_DEPTH);
        $depth = $v === null || $v === ''
            ? (int) config('editorial.comments.max_depth', 3)
            : (int) $v;

        return max(1, $depth);
    }

    /** wp: "Hold a comment if it contains N or more links" (0 = mati). */
    public static function maxLinks(): int
    {
        return max(0, (int) Setting::get(self::KEY_MAX_LINKS, 0));
    }

    /**
     * wp: "Comment Moderation" — kena → tahan di antrean (pending).
     *
     * @return array<int, string>
     */
    public static function moderationKeys(): array
    {
        return self::lines(Setting::get(self::KEY_MODERATION_KEYS));
    }

    /**
     * wp: "Disallowed Comment Keys" — kena → tandai spam.
     *
     * @return array<int, string>
     */
    public static function disallowedKeys(): array
    {
        return self::lines(Setting::get(self::KEY_DISALLOWED_KEYS));
    }

    /** @return array<int, string> */
    private static function lines(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $raw) ?: [],
        ), fn ($l) => $l !== ''));
    }

    /** @return array<string, mixed> Untuk mengisi form Filament. */
    public static function toArray(): array
    {
        return [
            self::KEY_ENABLED => self::enabled(),
            self::KEY_AUTO_APPROVE => self::autoApprove(),
            self::KEY_REQUIRE_EMAIL => self::requireEmail(),
            self::KEY_SUBSCRIPTIONS => self::subscriptionsEnabled(),
            self::KEY_CLOSE_AFTER_DAYS => self::closeAfterDays(),
            self::KEY_MAX_DEPTH => self::maxDepth(),
            self::KEY_MAX_LINKS => self::maxLinks(),
            self::KEY_MODERATION_KEYS => Setting::get(self::KEY_MODERATION_KEYS, ''),
            self::KEY_DISALLOWED_KEYS => Setting::get(self::KEY_DISALLOWED_KEYS, ''),
        ];
    }

    private static function boolSetting(string $key, bool $default): bool
    {
        $v = Setting::get($key);

        if ($v === null || $v === '') {
            return $default;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }
}
