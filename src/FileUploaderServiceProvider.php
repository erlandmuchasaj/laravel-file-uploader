<?php

namespace ErlandMuchasaj\LaravelFileUploader;

//use Illuminate\Foundation\Application as Laravel;
use Illuminate\Support\ServiceProvider;
class FileUploaderServiceProvider extends ServiceProvider
{

    /**
     * Package name.
     * Abstract type to bind FileUploader as in the Service Container.
     *
     * @var string
     */
    public static string $abstract = 'file-uploader';


    public function register()
    {
        //        if ($this->app instanceof Lumen) {
        //            $this->app->configure(static::$abstract);
        //        }

        $this->mergeConfigFrom(
            __DIR__ . '/../config/file-uploader.php',
            static::$abstract
        );
    }


    public function boot()
    {
        if ($this->app->runningInConsole()) {
//            if ($this->app instanceof Laravel) {
            $this->publishes([
                __DIR__ . '/../config/file-uploader.php' => config_path(static::$abstract . '.php'),
            ], 'config');
//            }
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
