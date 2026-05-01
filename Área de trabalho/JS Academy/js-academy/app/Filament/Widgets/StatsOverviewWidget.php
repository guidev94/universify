<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Progress;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Alunos', User::count())
                ->description('Usuários cadastrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Lições Concluídas', Progress::where('status', 'completed')->count())
                ->description('Progresso total da plataforma')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),
            Stat::make('XP Total Gerado', number_format(User::sum('xp')))
                ->description('XP acumulado por todos')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}
