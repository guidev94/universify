<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Progress;
use App\Models\PageView;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $viewsHoje = class_exists(\App\Models\PageView::class)
            ? PageView::whereDate('created_at', today())->count()
            : 0;
        $viewsSemana = class_exists(\App\Models\PageView::class)
            ? PageView::where('created_at', '>=', now()->subDays(7))->count()
            : 0;

        return [
            Stat::make('Total de Alunos', User::count())
                ->description(User::whereDate('created_at', today())->count() . ' novos hoje')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Aulas Concluídas', Progress::where('status', 'completed')->count())
                ->description(Progress::where('status', 'completed')->whereDate('updated_at', today())->count() . ' hoje')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),

            Stat::make('XP Total Gerado', number_format(User::sum('xp')))
                ->description('XP acumulado por todos os alunos')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),

            Stat::make('Visualizações Hoje', $viewsHoje)
                ->description($viewsSemana . ' nos últimos 7 dias')
                ->descriptionIcon('heroicon-m-eye')
                ->color('gray'),
        ];
    }
}
