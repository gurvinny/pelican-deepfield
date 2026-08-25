<?php

namespace Gurvinny\Deepfield\Providers;

use Dotenv\Dotenv;
use Gurvinny\Deepfield\DeepfieldPlugin;
use Illuminate\Support\ServiceProvider;
use Throwable;

class DeepfieldPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deepfield.php', 'deepfield');

        $this->app['config']->set('deepfield', $this->readSettings());
    }

    /**
     * Resolve settings from the panel's .env file rather than through env().
     *
     * env() reads the process environment, and Laravel's Dotenv repository is
     * immutable: it will not overwrite a variable that is already set. The PHP-FPM
     * pool ships with clear_env = no, so workers inherit the master environment.
     * Together that means any DEEPFIELD_* exported into the environment silently
     * wins over the .env file forever, and settings saved through the panel appear
     * to revert the next time the form is opened.
     *
     * Reading the file directly makes what was saved the value that is displayed.
     *
     * @return array<string, mixed>
     */
    protected function readSettings(): array
    {
        $stored = $this->readEnvFile();
        $settings = [];

        foreach (DeepfieldPlugin::SETTINGS as $key => $spec) {
            $settings[$key] = DeepfieldPlugin::coerce($key, $stored[$spec['env']] ?? null);
        }

        return $settings;
    }

    /**
     * Read DEEPFIELD_* keys out of the panel's .env file.
     *
     * Only this plugin's own keys are returned, so nothing else in .env is pulled
     * into memory. Nothing is written back to the process environment. Any failure
     * to read or parse yields an empty set, which leaves every setting on its
     * documented default rather than surfacing an error in the panel.
     *
     * @return array<string, string>
     */
    protected function readEnvFile(): array
    {
        $path = $this->app->environmentFilePath();

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        try {
            $parsed = Dotenv::parse((string) file_get_contents($path));
        } catch (Throwable) {
            return [];
        }

        $prefixed = [];

        foreach ($parsed as $key => $value) {
            if (str_starts_with($key, 'DEEPFIELD_') && is_string($value)) {
                $prefixed[$key] = $value;
            }
        }

        return $prefixed;
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
