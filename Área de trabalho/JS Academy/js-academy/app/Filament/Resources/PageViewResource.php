<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageViewResource\Pages;
use App\Models\PageView;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageViewResource extends Resource
{
    protected static ?string $model = PageView::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page')
                    ->label('Página')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->default('Visitante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('page')
                    ->label('Página')
                    ->options(fn () => PageView::distinct()->pluck('page', 'page')->toArray()),
                Tables\Filters\Filter::make('hoje')
                    ->label('Hoje')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('semana')
                    ->label('Últimos 7 dias')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $data = PageView::with('user')
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(fn ($pv) => [
                                'Página' => $pv->page,
                                'Usuário' => $pv->user?->name ?? 'Visitante',
                                'IP' => $pv->ip,
                                'Data' => $pv->created_at->format('d/m/Y H:i'),
                            ]);

                        $csv = collect([array_keys($data->first() ?? [])])
                            ->merge($data->map(fn ($row) => array_values($row)))
                            ->map(fn ($row) => implode(',', $row))
                            ->implode("\n");

                        return response()->streamDownload(
                            fn () => print($csv),
                            'analytics_' . now()->format('Y-m-d') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageViews::route('/'),
        ];
    }
}
