<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgressResource\Pages;
use App\Models\Progress;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgressResource extends Resource
{
    protected static ?string $model = Progress::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Progresso dos Alunos';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lesson.title')
                    ->label('Aula')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('lesson.track.name')
                    ->label('Trilha')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed'   => 'success',
                        'in_progress' => 'warning',
                        'skipped'     => 'danger',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última atividade')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['completed' => 'Concluído', 'in_progress' => 'Em progresso']),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $data = Progress::with(['user', 'lesson.track'])
                            ->orderBy('updated_at', 'desc')
                            ->get()
                            ->map(fn ($p) => [
                                'Aluno'  => $p->user?->name,
                                'Aula'   => $p->lesson?->title,
                                'Trilha' => $p->lesson?->track?->name,
                                'Status' => $p->status,
                                'Data'   => $p->updated_at->format('d/m/Y H:i'),
                            ]);

                        if ($data->isEmpty()) {
                            return;
                        }

                        $csv = collect([array_keys($data->first())])
                            ->merge($data->map(fn ($r) => array_values($r)))
                            ->map(fn ($r) => implode(',', $r))
                            ->implode("\n");

                        return response()->streamDownload(
                            fn () => print($csv),
                            'progresso_' . now()->format('Y-m-d') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }),
            ])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgresses::route('/'),
        ];
    }
}
