<?php

namespace App\Helpers;

class HerokuS3
{
    public static function get_aws_root_from_env() {
        if(!env('CLOUDCUBE_URL')) return null;

        $value = basename(Uri::get_uri_path(env('CLOUDCUBE_URL')));
        return empty($value) ? null : $value;
    }

    public static function get_aws_url_from_env() {
        if(!env('CLOUDCUBE_URL')) return null;

        $path = Uri::get_uri_path(env('CLOUDCUBE_URL'));
        $value = Uri::get_uri_without_path(env('CLOUDCUBE_URL'), $path);
        return empty($value) ? null : $value;
    }

    public static function get_default_aws_region() {
        if(!env('CLOUDCUBE_URL')) return null;

        $domainParts = Uri::get_uri_domain_parts(
            Uri::get_uri_host(env('CLOUDCUBE_URL'))
        );
        if(
            (count($domainParts) === 4) &&
            (strpos($domainParts[0], 'cloud-cube') !== false)
        ) {
            return self::get_default_cloudcube_region($domainParts[0]);
        }
        return null;
    }

    public static function get_aws_bucket_from_env() {
        if(!env('CLOUDCUBE_URL')) return null;

        $domainParts = Uri::get_uri_domain_parts(
            Uri::get_uri_host(env('CLOUDCUBE_URL'))
        );
        return count($domainParts) >= 1 ? $domainParts[0] : null;
    }

    public static function get_default_cloudcube_region($region) {
        switch($region) {
            case 'cloud-cube':
                return 'us-east-1';
            case 'cloud-cube-eu':
                return 'eu-west-1';
            case 'cloud-cube-jp':
                return 'ap-northeast-1';
        }
        return null;
    }
}
