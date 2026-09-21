<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\DiscussionSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Setara layar Settings → Discussion di WordPress. Mengontrol perilaku
 * komentar pengunjung situs (App\Models\Comment + CommentController).
 */
class ManageDiscussionSettings extends Page
{
    protected string $view = 'filament.pages.manage-site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Diskusi (Komentar)';

    protected static ?string $title = 'Pengaturan Diskusi';

    protected static ?int $navigationSort = 50;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ManageSiteSettings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(DiscussionSettings::toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Komentar artikel')
                    ->description('Berlaku untuk seluruh berita. Setiap artikel masih bisa ditutup sendiri lewat kolom "Komentar" di form berita.')
                    ->schema([
                        Toggle::make(DiscussionSettings::KEY_ENABLED)
                            ->label('Izinkan pengunjung mengirim komentar')
                            ->helperText('Jika dimatikan, form komentar hilang dan pengiriman ditolak.'),
                        Toggle::make(DiscussionSettings::KEY_AUTO_APPROVE)
                            ->label('Terbitkan komentar tanpa moderasi')
                            ->helperText('WordPress: "Comment must be manually approved" (kebalikan). Jika mati, komentar masuk antrean "Menunggu".'),
                        Toggle::make(DiscussionSettings::KEY_REQUIRE_EMAIL)
                            ->label('Wajibkan pengisi menyertakan email')
                            ->helperText('Email tidak dipublikasikan, hanya untuk moderasi.'),
                        Toggle::make(DiscussionSettings::KEY_SUBSCRIPTIONS)
                            ->label('Tawarkan "Beri tahu saya bila ada balasan"')
                            ->helperText('WordPress: "Notify me of follow-up comments by email". Pengomentar yang mencentangnya dikirimi email saat komentarnya dibalas; tautan berhenti-langganan ada di kaki setiap email.'),
                        TextInput::make(DiscussionSettings::KEY_CLOSE_AFTER_DAYS)
                            ->label('Tutup komentar otomatis setelah (hari)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('0 = tidak pernah ditutup. WordPress: "Automatically close comments on posts older than N days".'),
                        TextInput::make(DiscussionSettings::KEY_MAX_DEPTH)
                            ->label('Kedalaman balasan berulir maksimum')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('WordPress: "Enable threaded comments N levels deep".'),
                    ]),

                Section::make('Moderasi & filter kata')
                    ->description('Meniru bagian "Comment Moderation" dan "Disallowed Comment Keys" di WordPress → Settings → Discussion.')
                    ->schema([
                        TextInput::make(DiscussionSettings::KEY_MAX_LINKS)
                            ->label('Tahan komentar bila memuat tautan sebanyak ini atau lebih')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('WordPress: "Hold a comment in the queue if it contains N or more links." 0 = tidak diperiksa. Spam biasanya penuh tautan.'),
                        Textarea::make(DiscussionSettings::KEY_MODERATION_KEYS)
                            ->label('Kata kunci moderasi (tahan di antrean)')
                            ->rows(4)
                            ->helperText('Satu kata / frasa / nama / URL / email / alamat IP per baris. Komentar yang memuat salah satunya masuk antrean "Menunggu moderasi" — tidak langsung tayang.'),
                        Textarea::make(DiscussionSettings::KEY_DISALLOWED_KEYS)
                            ->label('Kata kunci terlarang (tandai spam)')
                            ->rows(4)
                            ->helperText('Satu per baris. Komentar yang memuat salah satunya langsung ditandai spam dan tidak memberi tahu editor. Pencocokan substring, tidak peka huruf besar/kecil.'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan')->submit('save'),
        ];
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value, 'group' => 'discussion']);
        }

        Notification::make()->title('Pengaturan diskusi disimpan.')->success()->send();
    }
}
