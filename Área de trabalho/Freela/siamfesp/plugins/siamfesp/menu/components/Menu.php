<?php namespace Siamfesp\Menu\Components;

use Cms\Classes\ComponentBase;

class Menu extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Menu',
            'description' => 'Renderiza um menu dinâmico'
        ];
    }
}
