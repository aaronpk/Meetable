<?php

namespace App\Helpers;

class Uri {
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