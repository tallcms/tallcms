<?php

namespace App\Filament\Resources\TallcmsMedia\Schemas;

use App\Models\MediaCollection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class TallcmsMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('upload')
                    ->label('Upload Files')
                    ->multiple()
                    ->directory('media')
                    ->disk('public')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/*', 'video/*', 'audio/*', 'application/pdf'])
                    ->maxSize(10240) // 10MB
                    ->previewable()
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull()
                    ->hiddenOn(['edit']),

                Placeholder::make('current_file_preview')
                    ->label('Current File')
                    ->content(function ($record) {
                        if (!$record || !$record->path) {
                            return 'No file uploaded';
                        }
                        
                        $url = \Storage::disk($record->disk)->url($record->path);
                        
                        if ($record->is_image) {
                            return new HtmlString("
                                <div class='space-y-2'>
                                    <img src='{$url}' alt='{$record->alt_text}' class='max-w-xs max-h-48 rounded-lg border'>
                                    <div class='text-sm text-gray-600'>
                                        <div>File: {$record->file_name}</div>
                                        <div>Size: {$record->human_size}</div>
                                        <div>Type: {$record->mime_type}</div>
                                        " . ($record->dimensions ? "<div>Dimensions: {$record->dimensions}</div>" : "") . "
                                    </div>
                                    <a href='{$url}' target='_blank' class='inline-flex items-center text-sm text-blue-600 hover:text-blue-800'>
                                        View Full Size
                                    </a>
                                </div>
                            ");
                        }
                        
                        return new HtmlString("
                            <div class='space-y-2'>
                                <div class='text-sm text-gray-600'>
                                    <div>File: {$record->file_name}</div>
                                    <div>Size: {$record->human_size}</div>
                                    <div>Type: {$record->mime_type}</div>
                                </div>
                                <a href='{$url}' target='_blank' class='inline-flex items-center text-sm text-blue-600 hover:text-blue-800'>
                                    Download File
                                </a>
                            </div>
                        ");
                    })
                    ->columnSpanFull()
                    ->visibleOn(['edit']),

                FileUpload::make('new_file')
                    ->label('Replace File')
                    ->directory('media')
                    ->disk('public')
                    ->visibility('public')
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
                    ->nullable(),

                TextInput::make('alt_text')
                    ->label('Alt Text')
                    ->helperText('Describe the image for accessibility')
                    ->maxLength(255),

                Textarea::make('caption')
                    ->label('Caption')
                    ->maxLength(500)
                    ->rows(3),
            ]);
    }
}
