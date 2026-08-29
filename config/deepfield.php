<?php

/*
 * Defaults only.
 *
 * At runtime DeepfieldPluginProvider overwrites this whole key with values read
 * straight from the panel's .env file. env() is deliberately not used here: it
 * reads the process environment, which silently overrides .env and makes saved
 * settings appear to revert. See DeepfieldPluginProvider::readSettings().
 */

return [
    'theme_mode'          => 'dark',                                     // dark|light|system — the panel default, not a lock
    'panel_colors'        => true,                                       // false hands Filament's colour roles to another plugin
    'console_always_dark' => true,                                       // keep the terminal dark while the panel is light
    'star_density'      => 'medium',                                     // off|low|medium|high
    'nebula_enabled'    => true,
    'nebula_hue'        => 'violet',                                     // violet|teal|rose
    'crt_bloom'         => true,
    'reduce_motion'     => false,
    'terminal_palette'  => 'cosmic',                                     // cosmic|minecraft|solarized_aurora|nord_aurora
    'scanline_density'  => 'normal',                                     // off|fine|normal|heavy
    'audio_cues'        => false,                                        // opt-in server state audio
    'tab_title_suffix'  => true,                                         // append "· Deepfield"
];
