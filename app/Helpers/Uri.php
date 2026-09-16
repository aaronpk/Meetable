<?php

namespace App\Helpers;

class Uri {
    // True if the URL has a scheme other than http or https, such as javascript: or data:.
    // Browsers ignore whitespace and control characters in a scheme, so those are removed first.
    public static function has_unsafe_scheme($url) {
        $url = preg_replace('/[\x00-\x20\x7f]/', '', (string)$url);

        if(preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $match))
            return !in_array(strtolower($match[1]), ['http', 'https']);

        return false;
    }

    // Returns the URL if it's safe to put in an href or src attribute, or an empty string
    public static function safe_href($url) {
        if(!$url || self::has_unsafe_scheme($url))
            return '';

        return $url;
    }

    // Returns the URL if it points to this website, so it's safe to redirect to after
    // logging in. Anything else, including protocol-relative "//host" URLs, becomes "/".
    public static function same_origin_path($url) {
        $url = (string)$url;

        if($url === '' || preg_match('/[\\\\\x00-\x20\x7f]/', $url))
            return '/';

        $parts = parse_url($url);
        if($parts === false)
            return '/';

        if(!isset($parts['scheme']) && !isset($parts['host']))
            return (substr($url, 0, 1) == '/' && substr($url, 0, 2) != '//') ? $url : '/';

        $allowed_hosts = array_filter([
            request()->getHost(),
            parse_url((string)env('APP_URL'), PHP_URL_HOST),
        ]);

        if(in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'])
            && isset($parts['host'])
            && in_array(strtolower($parts['host']), array_map('strtolower', $allowed_hosts))
            && !isset($parts['user']))
            return $url;

        return '/';
    }

    // True if $url is on the same host as $base and, when $base has a path, within that path.
    // https://github.com/someone covers https://github.com/someone/repo but not https://github.com/other
    public static function url_is_under($url, $base) {
        $url_parts = parse_url((string)$url);
        $base_parts = parse_url((string)$base);

        if(empty($url_parts['host']) || empty($base_parts['host']))
            return false;

        if(strtolower($url_parts['host']) != strtolower($base_parts['host']))
            return false;

        $base_path = rtrim($base_parts['path'] ?? '', '/');
        if($base_path == '')
            return true;

        $path = $url_parts['path'] ?? '';
        return $path == $base_path || strpos($path, $base_path.'/') === 0;
    }

    public static function get_uri_path($url) {
        $parsed = parse_url($url);
        return array_key_exists('path', $parsed) ? $parsed['path'] : '';
    }
    
    public static function get_uri_without_path($url, $path=null) {
        $url = $url ?? '';
        $path = $path ?? '';
        return substr($url, 0, strlen($url) - strlen($path));
    }
    
    public static function get_uri_host($url) {
        $parsed = parse_url($url);
        return array_key_exists('host', $parsed) ? $parsed['host'] : '';
    }
    
    public static function get_uri_domain_parts($hoststr) {
        return array_filter(
            explode('.', $hoststr),
            function($item) { return !empty($item); }
        );
    }    
}