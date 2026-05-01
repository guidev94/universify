<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Lições';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('track_id')
                    ->relationship('track', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('xp_reward')
                    ->numeric()
                    ->default(100),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('task')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('starter_code')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('solution_code')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('tips')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('examples')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('real_world_case')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('docs_link')
                    ->url()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('track.name')
                    ->label('Trilha')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordem')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('xp_reward')
                    ->label('XP')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
