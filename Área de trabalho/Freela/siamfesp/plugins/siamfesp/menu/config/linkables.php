<?php

return [
    'associate_category' => [
        'label' => 'Categoria de Associados',
        'model' => \Siamfesp\Associados\Models\AssociateCategory::class,
        'titleColumn' => 'name',
        'slugColumn' => 'slug',
        'pageName' => 'associados',
        'pageParam' => 'categoria',
        'routePrefix' => 'associados',
        'publishedColumn' => 'is_published',
        'orderBy' => [
            ['sort_order', 'asc'],
            ['name', 'asc'],
        ],
    ],
    'pdf_theme' => [
        'label' => 'Categoria de PDF / Revista',
        'model' => \Siamfesp\Pdf\Models\Theme::class,
        'titleColumn' => 'title',
        'slugColumn' => 'title',
        'useMenuSlug' => true,
        'pageName' => 'revista',
        'pageParam' => 'categoria',
        'routePrefix' => 'servicos/pdf',
        'publishedColumn' => null,
        'orderBy' => [
            ['title', 'asc'],
        ],
    ],


    'agenda_event' => [
        'label' => 'Agenda / Evento',
        'model' => \Siamfesp\Agenda\Models\Event::class,
        'titleColumn' => 'title',
        'slugColumn' => 'slug',
        'pageName' => 'agenda',
        'pageParam' => 'slug',
        'routePrefix' => 'agenda',
        'publishedColumn' => 'is_published',
        'orderBy' => [
            ['event_date', 'desc'],
            ['title', 'asc'],
        ],
    ],


    'video' => [
        'label' => 'Vídeo',
        'model' => \Siamfesp\Videos\Models\Video::class,
        'titleColumn' => 'title',
        'slugColumn' => 'slug',
        'pageName' => 'videos',
        'pageParam' => 'slug',
        'routePrefix' => 'videos',
        'publishedColumn' => 'is_published',
        'orderBy' => [
            ['sort_order', 'asc'],
            ['title', 'asc'],
        ],
    ],

    'pdf_document' => [
        'label' => 'PDF / Revista',
        'model' => \Siamfesp\Pdf\Models\Document::class,
        'titleColumn' => 'title',
        'urlColumn' => 'pdf',
        'isMediaPath' => true,
        'orderBy' => [
            ['sort_order', 'asc'],
            ['id', 'desc'],
        ],
    ],
];
