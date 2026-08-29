<?php

return [
    'saved'                => 'Deepfield settings saved.',

    'section_theme'           => 'Theme Mode & Colors',
    'section_theme_help'      => 'How Deepfield sits alongside the panel\'s own theme switcher and any other theme plugin you have installed.',

    'section_background'      => 'Background & Atmosphere',
    'section_background_help' => 'The canvas layers that drift behind every page. These are the settings to turn down first on older hardware.',

    'section_console'         => 'Server Console',
    'section_console_help'    => 'Terminal colors and CRT treatment on the in-server console page.',

    'section_motion'          => 'Motion & Accessibility',
    'section_motion_help'     => 'Animation preferences. System settings are always honoured on top of these.',

    'section_chrome'          => 'Interface Chrome',
    'section_chrome_help'     => 'Small cosmetic touches outside the main theme.',

    'theme_mode'           => 'Default theme mode',
    'theme_mode_help'      => 'What new users get before they pick for themselves. Deepfield ships both a dark and a light palette. This is only a default — the panel\'s theme switcher still belongs to each user, and their choice is remembered.',
    'mode_dark'            => 'Dark — deep cosmic (default)',
    'mode_light'           => 'Light — cool observatory white',
    'mode_system'          => 'Follow the operating system',

    'panel_colors'         => 'Apply Deepfield\'s component colors',
    'panel_colors_help'    => 'Sets Filament\'s primary / gray / danger / success / warning / info roles to match the theme. Turn this off to hand those colors to another plugin, such as Theme Customizer, and Deepfield will leave them alone.',

    'console_always_dark'  => 'Keep the console dark in light mode',
    'console_always_dark_help' => 'A terminal is a dark object, so by default the console screen keeps its dark palette even when the rest of the panel is light. Turn this off to let the console follow the light theme too — note that all four terminal palettes above are dark-on-dark, so a light console uses one shared light palette instead and the Minecraft preset stops matching vanilla 1:1.',

    'star_density'         => 'Starfield density',
    'star_density_help'    => 'How many stars drift behind your panel. Lower it if you notice fan spin on older hardware.',
    'density_off'          => 'Off',
    'density_low'          => 'Low (~250)',
    'density_medium'       => 'Medium (~600)',
    'density_high'         => 'High (~1200)',

    'nebula_enabled'       => 'Nebula fog',
    'nebula_enabled_help'  => 'Soft, slow-drifting cloud layer beneath the stars. Adds depth; disable for the cleanest look.',

    'nebula_hue'           => 'Nebula hue',
    'nebula_hue_help'      => 'Dominant color of the fog layer.',
    'hue_violet'           => 'Violet (default)',
    'hue_teal'             => 'Teal',
    'hue_rose'             => 'Rose',

    'crt_bloom'            => 'CRT bloom',
    'crt_bloom_help'       => 'Phosphor glow around the console frame. Scanlines are set separately below.',

    'reduce_motion'        => 'Reduce motion',
    'reduce_motion_help'   => 'Disable parallax, meteors and the scanline drift. `prefers-reduced-motion` is always respected regardless of this setting.',

    'terminal_palette'     => 'Terminal palette',
    'terminal_palette_help'=> 'Color scheme used inside the in-server console (xterm.js). Cosmic matches the rest of the theme; Minecraft mimics vanilla § colors 1:1.',
    'palette_cosmic'       => 'Cosmic (default)',
    'palette_minecraft'    => 'Minecraft Vanilla',
    'palette_solarized'    => 'Solarized Aurora',
    'palette_nord'         => 'Nord Aurora',

    'scanline_density'     => 'CRT scanline density',
    'scanline_density_help'=> 'Horizontal scanline overlay on the console. Independent of CRT bloom.',
    'scan_off'             => 'Off',
    'scan_fine'            => 'Fine (tight, subtle)',
    'scan_normal'          => 'Normal (default)',
    'scan_heavy'           => 'Heavy (thick, pronounced)',

    'audio_cues'           => 'Audio cues on server state change',
    'audio_cues_help'      => 'Play a short WebAudio tone when a server transitions to running / starting / offline. Off by default.',

    'tab_title_suffix'     => 'Append " · Deepfield" to the browser tab title',
    'tab_title_suffix_help'=> 'Purely cosmetic — makes browser tabs immediately identifiable when you have several panels open.',
];
