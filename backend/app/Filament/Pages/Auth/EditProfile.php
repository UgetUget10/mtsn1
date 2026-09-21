<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Halaman "Profil Saya" self-service — setara wp-admin/profile.php: SETIAP
 * user (kontributor/editor/super_admin) bisa mengedit biodata/foto/sosial
 * miliknya sendiri, terlepas dari apakah dia punya izin resource Pengguna.
 * Field & pengelompokan sengaja disamakan dengan App\Filament\Resources\
 * Users\Schemas\UserForm "Profil publik" — SATU-SATUNYA yang sengaja
 * dihilangkan adalah pengelolaan Peran (role), karena user tidak boleh
 * menaikkan hak aksesnya sendiri.
 */
class EditProfile extends BaseEditProfile
{
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
                $this->getApiTokensContentComponent(),
            ]);
    }

    /**
     * "Application Passwords" ala WordPress (Users → Profile → Application
     * Passwords) — token API pribadi lewat Sanctum, dibuat/dicabut sendiri
     * oleh user tanpa perlu peran Pengguna. Nilai token HANYA ditampilkan
     * sekali saat dibuat (sifat token hash Sanctum — tidak bisa dibaca ulang
     * dari DB), persis perilaku Application Passwords WordPress.
     */
    public function getApiTokensContentComponent(): Component
    {
        return Section::make('Token API')
            ->description('Untuk integrasi eksternal (mis. aplikasi pihak ketiga) yang perlu mengakses data atas nama Anda tanpa memakai kata sandi akun. Setara "Application Passwords" di WordPress.')
            ->schema([
                ViewComponent::make('filament.pages.auth.api-tokens'),
            ]);
    }

    /** @return Collection<int, PersonalAccessToken> */
    public function getApiTokens(): Collection
    {
        return $this->getUser()->tokens()->latest()->get();
    }

    public function createApiTokenAction(): Action
    {
        return Action::make('createApiToken')
            ->label('Buat token baru')
            ->icon('heroicon-o-plus')
            ->schema([
                TextInput::make('name')
                    ->label('Nama token')
                    ->placeholder('mis. Aplikasi Mobile Humas')
                    ->required()
                    ->maxLength(100),
            ])
            ->action(function (array $data) {
                $plainTextToken = $this->getUser()->createToken($data['name'])->plainTextToken;

                Notification::make()
                    ->title('Token dibuat')
                    ->body('Salin sekarang — token ini TIDAK akan ditampilkan lagi setelah ditutup.')
                    ->success()
                    ->persistent()
                    ->actions([
                        NotificationAction::make('copy')
                            ->label($plainTextToken)
                            ->color('gray'),
                    ])
                    ->send();
            });
    }

    public function revokeApiTokenAction(): Action
    {
        return Action::make('revokeApiToken')
            ->label('Cabut')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cabut token ini?')
            ->modalDescription('Aplikasi yang memakai token ini akan langsung kehilangan akses. Tindakan ini tidak bisa dibatalkan.')
            ->action(function (array $arguments) {
                $this->getUser()->tokens()->where('id', $arguments['id'])->delete();

                Notification::make()->title('Token dicabut')->success()->send();
            });
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun')
                    ->columns(2)
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),

                Section::make('Profil publik')
                    ->description('Tampil di halaman arsip penulis (/penulis/{slug}) dan sebagai byline artikel. Setara "Biographical Info" di WordPress.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_publicly')
                            ->label('Tampilkan profil ini sebagai penulis publik')
                            ->columnSpanFull(),
                        TextInput::make('job_title')
                            ->label('Jabatan / peran')
                            ->placeholder('mis. Guru Bahasa Indonesia'),
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatar')
                            ->label('Foto profil')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->imageEditor(),
                        Textarea::make('bio')
                            ->label('Biografi singkat')
                            ->rows(4)
                            ->maxLength(600)
                            ->columnSpanFull(),
                        TextInput::make('social.website')->label('Situs web')->url()->prefixIcon('heroicon-o-globe-alt'),
                        TextInput::make('social.instagram')->label('Instagram')->url(),
                        TextInput::make('social.twitter')->label('X / Twitter')->url(),
                        TextInput::make('social.linkedin')->label('LinkedIn')->url(),
                        TextInput::make('social.scholar')->label('Google Scholar')->url(),
                    ]),
            ]);
    }
}
