<?php

/*
|--------------------------------------------------------------------------
| View Storage Paths
|--------------------------------------------------------------------------
|
| Most templating systems load templates from disk. Here you may specify
| an array of paths that should be checked for your views. Of course
| the usual Laravel view path has already been registered for you.
|
*/

$compiled = env('VIEW_COMPILED_PATH');

/*
| Never fall back to realpath(). On shared hosting the compiled directory can
| be missing (empty directories are not kept by Git/ZIP deployments), and
| realpath() then returns false, which Laravel rejects with:
| "Please provide a valid cache path."
|
| storage_path() is an absolute path that does not depend on the directory
| existing yet; App\Providers\AppServiceProvider recreates it on boot.
*/
if (! is_string($compiled) || trim($compiled) === '') {
    $compiled = storage_path('framework/views');
}

return [

    'paths' => [
        resource_path('views'),
    ],

    'compiled' => $compiled,

];
