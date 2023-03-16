<?php

namespace ErlandMuchasaj\LaravelFileUploader;

//use Illuminate\Foundation\Application as Laravel;
use Illuminate\Support\ServiceProvider;

class FileUploaderServiceProvider extends ServiceProvider
{
    /**
     * Package name.
     * Abstract type to bind FileUploader as in the Service Container.
     */
    public static string $abstract = 'file-uploader';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/file-uploader.php',
            static::$abstract
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/file-uploader.php' => config_path(static::$abstract.'.php'),
            ], 'config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [static::$abstract];
    }
}
