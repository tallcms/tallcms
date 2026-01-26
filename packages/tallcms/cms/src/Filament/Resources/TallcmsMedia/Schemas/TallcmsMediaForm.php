<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use TallCms\Cms\Models\MediaCollection;

class TallcmsMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('upload')
                    ->label('Upload Files')
                    ->multiple()
                    ->maxFiles(50)
                    ->directory('media')
                    ->disk(\cms_media_disk())
                    ->visibility(\cms_media_visibility())
                    ->acceptedFileTypes(['image/*', 'video/*', 'audio/*', 'application/pdf'])
                    ->maxSize(20480) // 20MB
                    ->storeFileNamesIn('original_names')
                    ->previewable()
                    ->downloadable()
                    ->openable()
                    ->panelLayout('grid')
                    ->columnSpanFull()
                    ->hiddenOn(['edit']),

                Placeholder::make('current_file_preview')
                    ->label('Current File')
                    ->content(function ($record) {
                        if (! $record || ! $record->path) {
                            return 'No file uploaded';
                        }

                        $url = \Storage::disk($record->disk)->url($record->path);

                        if ($record->is_image) {
                            $altText = e($record->alt_text ?? '');
                            $fileName = e($record->file_name ?? '');
                            $humanSize = e($record->human_size ?? '');
                            $mimeType = e($record->mime_type ?? '');
                            $dimensions = $record->dimensions ? e($record->dimensions) : '';

                            return new HtmlString("
                                <div class='space-y-2'>
                                    <a href='".e($url)."' target='_blank' class='block w-fit'>
                                        <img src='".e($url)."' alt='{$altText}' class='max-w-xs max-h-48 rounded-lg border hover:opacity-90 transition-opacity cursor-pointer'>
                                    </a>
                                    <div class='text-sm text-gray-600 dark:text-gray-400'>
                                        <div>{$fileName}</div>
                                        <div>{$humanSize} · {$mimeType}".($dimensions ? " · {$dimensions}" : '')."</div>
                                    </div>
                                </div>
                            ");
                        }

                        $fileName = e($record->file_name ?? '');
                        $humanSize = e($record->human_size ?? '');
                        $mimeType = e($record->mime_type ?? '');

                        return new HtmlString("
                            <div class='space-y-2'>
                                <div class='text-sm text-gray-600 dark:text-gray-400'>
                                    <div>{$fileName}</div>
                                    <div>{$humanSize} · {$mimeType}</div>
                                </div>
                                <a href='".e($url)."' target='_blank' class='inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400'>
                                    <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'/></svg>
                                    Download
                                </a>
                            </div>
                        ");
                    })
                    ->columnSpanFull()
                    ->visibleOn(['edit']),

                FileUpload::make('new_file')
                    ->label('Replace File')
                    ->directory('media')
                    ->disk(\cms_media_disk())
                    ->visibility(\cms_media_visibility())
                    ->acceptedFileTypes(['image/*', 'video/*', 'audio/*', 'application/pdf'])
                    ->maxSize(10240) // 10MB
                    ->previewable()
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull()
                    ->helperText('Upload a new file to replace the current one (leave empty to keep existing file)')
                    ->visibleOn(['edit']),

                TextInput::make('name')
                    ->label('File Name')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn(['edit']),

                // For bulk upload - manual handling (relationship() only syncs to returned record)
                Select::make('collection_ids')
                    ->label('Collections')
                    ->multiple()
                    ->options(fn () => MediaCollection::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->visibleOn(['create'])
                    ->helperText('Selected collections will be applied to all uploaded files')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Collection Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(MediaCollection::class, 'name'),

                        Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return MediaCollection::create($data)->id;
                    }),

                // For edit - automatic relationship sync
                Select::make('collections')
                    ->label('Collections')
                    ->relationship('collections', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Collection Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(MediaCollection::class, 'name'),

                        Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(2),
                    ])
                    ->visibleOn(['edit']),

                TextInput::make('alt_text')
                    ->label('Alt Text')
                    ->helperText('Describe the image for accessibility. Recommended: under 125 characters.')
                    ->maxLength(255)
                    ->hint(fn ($state) => strlen($state ?? '') . '/125 chars')
                    ->suffixAction(
                        Action::make('generate_alt')
                            ->icon('heroicon-m-sparkles')
                            ->tooltip('Generate from filename')
                            ->action(fn ($set, $get) => $set('alt_text', Str::headline(
                                pathinfo($get('name') ?? '', PATHINFO_FILENAME)
                            )))
                    ),

                Textarea::make('caption')
                    ->label('Caption')
                    ->maxLength(500)
                    ->rows(3),
            ]);
    }
}
