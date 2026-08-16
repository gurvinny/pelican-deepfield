<?php

return [
    'star_density'      => env('DEEPFIELD_STAR_DENSITY', 'medium'),
    'nebula_enabled'    => env('DEEPFIELD_NEBULA_ENABLED', true),
    'nebula_hue'        => env('DEEPFIELD_NEBULA_HUE', 'violet'),
    'crt_bloom'         => env('DEEPFIELD_CRT_BLOOM', true),
    'reduce_motion'     => env('DEEPFIELD_REDUCE_MOTION', false),
    'terminal_palette'  => env('DEEPFIELD_TERMINAL_PALETTE', 'cosmic'),   // cosmic|minecraft|solarized_aurora|nord_aurora
    'scanline_density'  => env('DEEPFIELD_SCANLINE_DENSITY', 'normal'),   // fine|normal|heavy
];
