<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID', env('CLOUDCUBE_ACCESS_KEY_ID')),
            'secret' => env('AWS_SECRET_ACCESS_KEY', env('CLOUDCUBE_SECRET_ACCESS_KEY')),
            'region' => env('AWS_DEFAULT_REGION', HerokuS3::get_default_aws_region()),
            'bucket' => env('AWS_BUCKET', HerokuS3::get_aws_bucket_from_env()),
            'url' => env('AWS_URL', HerokuS3::get_aws_url_from_env()),
            'root' => env('AWS_ROOT', HerokuS3::get_aws_root_from_env()),
        ],

    ],

];

function get_url_path($url) {
    $parsed = parse_url($url);
    return array_key_exists('path', $parsed) ? $parsed['path'] : '';
}

function get_url_without_path($url, $path=null) {
    $path = $path ?? '';
    return substr($url, 0, strlen($url) - strlen($path));
}

function get_aws_bucket_from_env() {
    $value = basename(get_url_path(env('AWS_URL')));
    return empty($value) ? null : $value;
}
function get_aws_url_from_env() {
    $path = get_url_path(env('AWS_URL'));
    $value = get_url_without_path(env('AWS_URL'), $path);
    return empty($value) ? null : $value;
}
