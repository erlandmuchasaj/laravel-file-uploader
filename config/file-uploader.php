<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the package.
    |
    */

    'disk' => env('FILESYSTEM_DISK', 'local'),

    'visibility' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Filesystem path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path structure of the uploaded files
    | uploads/{user_id}/{type}/{filename}
    |
    | Supported variables: "{user_id}", "{type}", "{filename}"
    |
    */

    'path' => 'uploads/{user_id}/{type}/{filename}',

    /*
    |--------------------------------------------------------------------------
    | Default files user group
    |--------------------------------------------------------------------------
    |
    | All files will be grouped by default to root user aka ID=1.
    | If you have a different default user id you can set it here.
    |
    */

    'user_id' => 1,

    /*
    |--------------------------------------------------------------------------
    | Shall we use safe extension and name extraction
    |--------------------------------------------------------------------------
    |
    | getClientOriginalName() and getClientOriginalExtension()
    | are considered unsafe.
    |
    */

    'safe' => false,
];
