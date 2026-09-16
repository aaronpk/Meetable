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