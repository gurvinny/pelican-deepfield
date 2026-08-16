<?php

namespace Gurvinny\Deepfield\Providers;

use Illuminate\Support\ServiceProvider;

class DeepfieldPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deepfield.php', 'deepfield');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'deepfield');

        $this->publishes([
            __DIR__.'/../../config/deepfield.php' => config_path('deepfield.php'),
        ], 'deepfield-config');

        $this->publishes([
            __DIR__.'/../../css'   => public_path('plugins/deepfield'),
            __DIR__.'/../../js'    => public_path('plugins/deepfield'),
            __DIR__.'/../../fonts' => public_path('plugins/deepfield/fonts'),
        ], 'deepfield-assets');
    }
}
