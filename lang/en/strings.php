<?php

return [
    'saved'                => 'Deepfield settings saved.',

    'star_density'         => 'Starfield density',
    'star_density_help'    => 'How many stars drift behind your panel. Lower it if you notice fan spin on older hardware.',
    'density_off'          => 'Off',
    'density_low'          => 'Low (~250)',
    'density_medium'       => 'Medium (~600)',
    'density_high'         => 'High (~1200)',

    'nebula_enabled'       => 'Nebula fog',
    'nebula_enabled_help'  => 'Soft, slow-drifting cloud layer beneath the stars. Adds depth; disable for the cleanest look.',

    'nebula_hue'           => 'Nebula hue',
    'hue_violet'           => 'Violet (default)',
    'hue_teal'             => 'Teal',
    'hue_rose'             => 'Rose',

    'crt_bloom'            => 'CRT bloom on server console',
    'crt_bloom_help'       => 'Adds a subtle phosphor glow and scanlines to the in-game-server console page.',

    'reduce_motion'        => 'Reduce motion',
    'reduce_motion_help'   => 'Disable parallax and meteor animation. `prefers-reduced-motion` is always respected regardless of this setting.',

    'terminal_palette'     => 'Terminal palette',
    'terminal_palette_help'=> 'Color scheme used inside the in-server console (xterm.js). Cosmic matches the rest of the theme; Minecraft mimics vanilla § colors 1:1.',
    'palette_cosmic'       => 'Cosmic (default)',
    'palette_minecraft'    => 'Minecraft Vanilla',
    'palette_solarized'    => 'Solarized Aurora',
    'palette_nord'         => 'Nord Aurora',

    'scanline_density'     => 'CRT scanline density',
    'scanline_density_help'=> 'How prominent the console scanlines are. Only visible when CRT bloom is on.',
    'scan_fine'            => 'Fine',
    'scan_normal'          => 'Normal',
    'scan_heavy'           => 'Heavy',
];
