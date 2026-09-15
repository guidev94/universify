<?php

declare(strict_types=1);

/**
 * interesse_score → temperatura mapping.
 *
 * Ranges are inclusive on both ends except Prioritário (score >= prioritario_min).
 */
return [
    'prioritario_min' => 101,
    'quente_min'      => 61,
    'quente_max'      => 100,
    'morno_min'       => 21,
    'morno_max'       => 60,
    'frio_max'        => 20,
];
