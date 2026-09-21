<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * "Draf Kilat" — meniru widget Quick Draft dashboard WordPress: tulis judul +
 * isi singkat, simpan langsung sebagai draft berita tanpa membuka form penuh.
 */
class QuickDraft extends Widget implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.quick-draft';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Apa yang sedang terjadi di madrasah?'),
                Textarea::make('body')
                    ->label('Isi singkat')
                    ->rows(4)
                    ->placeholder('Tulis pokok beritanya. Bisa dilengkapi nanti.'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $locale = array_key_first(config('translatable.locales', ['id' => null])) ?? 'id';

        $post = new Post;
        $post->setTranslation('title', $locale, $data['title']);
        if (filled($data['body'] ?? null)) {
            $post->setTranslation('body', $locale, '<p>'.e($data['body']).'</p>');
        }
        $post->status = Post::STATUS_DRAFT;
        $post->user_id = auth()->id();
        $post->save();

        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Draf tersimpan')
            ->body('Berita "'.Str::limit($data['title'], 40).'" disimpan sebagai draft.')
            ->actions([
                Action::make('edit')
                    ->label('Lengkapi sekarang')
                    ->url(route('filament.admin.resources.posts.edit', ['record' => $post]))
                    ->button(),
            ])
            ->send();
    }
}
