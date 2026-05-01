<?php

namespace App\Filament\Resources\LogicChallengeResource\Pages;

use App\Filament\Resources\LogicChallengeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLogicChallenges extends ListRecords
{
    protected static string $resource = LogicChallengeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
