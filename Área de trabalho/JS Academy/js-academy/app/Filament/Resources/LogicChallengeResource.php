<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogicChallengeResource\Pages;
use App\Models\LogicChallenge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LogicChallengeResource extends Resource
{
    protected static ?string $model = LogicChallenge::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Desafios';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('category')
                ->required(),
            Forms\Components\Select::make('difficulty')
                ->options(['Fácil' => 'Fácil', 'Médio' => 'Médio', 'Difícil' => 'Difícil'])
                ->required(),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('task')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('starter_code')
                ->rows(6)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('xp_reward')
                ->numeric()
                ->default(100),
            Forms\Components\TextInput::make('diamond_reward')
                ->numeric()
                ->default(10),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable(),
            Tables\Columns\TextColumn::make('category')
                ->badge(),
            Tables\Columns\TextColumn::make('difficulty')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Fácil'  => 'success',
                    'Médio'  => 'warning',
                    'Difícil' => 'danger',
                    default  => 'gray',
                }),
            Tables\Columns\TextColumn::make('xp_reward')
                ->label('XP')
                ->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLogicChallenges::route('/'),
            'create' => Pages\CreateLogicChallenge::route('/create'),
            'edit'   => Pages\EditLogicChallenge::route('/{record}/edit'),
        ];
    }
}
