<?php
namespace App\Helpers;

use p3k\HTTP\Transport;
use p3k\XRay;

/**
 * An HTTP client for fetching URLs that come from users or from other websites.
 *
 * Only http and https are allowed, and every request (including each redirect) must
 * go to a public IP address. The address that was checked is pinned for the connection,
 * so a DNS record that changes between the check and the request can't be used to
 * reach an internal service.
 *
 * Set ALLOW_PRIVATE_NETWORK_REQUESTS=true to allow private addresses in development.
 */
class SafeHTTP implements Transport {

    const MAX_BYTES = 10485760; // 10MB

    protected $_timeout = 10;
    protected $_max_redirects = 5;

    // Ranges that FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE don't cover
    const BLOCKED_RANGES = [
        '0.0.0.0/8',
        '100.64.0.0/10',   // Carrier-grade NAT
        '192.0.0.0/24',
        '198.18.0.0/15',
        '224.0.0.0/4',     // Multicast
        '240.0.0.0/4',
        '::/128',
        '::1/128',
        '::ffff:0:0/96',   // IPv4-mapped
        '64:ff9b::/96',    // NAT64
        '2002::/16',       // 6to4
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    public function set_timeout($timeout) {
        $this->_timeout = $timeout;
    }

    public function set_max_redirects($max) {
        $this->_max_redirects = min($max, 5);
    }

    public function get($url, $headers=[]) {
        return $this->request('GET', $url, null, $headers);
    }

    public function post($url, $body, $headers=[]) {
        return $this->request('POST', $url, $body, $headers);
    }

    public function put($url, $body, $headers=[]) {
        return $this->request('PUT', $url, $body, $headers);
    }

    public function head($url, $headers=[]) {
        return $this->request('HEAD', $url, null, $headers);
    }

    // Fetches a URL and returns the response body, or null if it couldn't be fetched
    public static function fetch($url) {
        $http = new self();
        $response = $http->get($url, ['User-Agent: '.HTTP::user_agent()]);

        if($response['error'] || $response['code'] < 200 || $response['code'] >= 300) {
            \Log::warning('Fetching '.$url.' failed: '.($response['error_description'] ?: 'HTTP '.$response['code']));
            return null;
        }

        return $response['body'];
    }

    // Fetches a URL and returns the body only if it is a JPEG, PNG, GIF or WebP image.
    // Checking the bytes first also stops the image library treating a body that looks
    // like a URL or file path as something to open.
    public static function fetch_image($url) {
        $body = self::fetch($url);

        if(!$body)
            return null;

        $info = @getimagesizefromstring($body);
        if(!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
            \Log::warning('The file at '.$url.' is not a supported image');
            return null;
        }

        return $body;
    }

    // XRay passes along connection errors from curl, which would tell whoever sent the URL
    // which hosts and ports are reachable. Only its parsing errors are shown as they are.
    public static function xray_error_description($data) {
        $fetch_errors = ['dns_error', 'connect_error', 'timeout', 'ssl_error', 'ssl_cert_error',
            'ssl_unsupported_cipher', 'too_many_redirects', 'unknown'];

        if(in_array($data['error'] ?? null, $fetch_errors))
            return 'The source URL could not be fetched';

        return $data['error_description'] ?? 'The source URL could not be parsed';
    }

    // An XRay parser that can only make requests through this client
    public static function xray() {
        $xray = new XRay();
        $xray->http = new SafeXRayHTTP($xray->http);
        return $xray;
    }

    public function request($method, $url, $body, $headers) {
        for($redirects = 0; ; $redirects++) {
            list($error, $host, $port, $ips) = self::check_url($url);
            if($error)
                return self::error_response($url, $error[0], $error[1]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT_MS => 4000,
                CURLOPT_TIMEOUT_MS => (int)round($this->_timeout * 1000),
                CURLOPT_MAXFILESIZE_LARGE => self::MAX_BYTES,
                CURLOPT_NOPROGRESS => false,
                CURLOPT_XFERINFOFUNCTION => function($ch, $download_total, $downloaded) {
                    // Returning non-zero aborts the transfer
                    return $downloaded > self::MAX_BYTES ? 1 : 0;
                },
            ]);
            // Connect to the addresses that were checked, rather than looking the name up again
            if(!filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
                curl_setopt($ch, CURLOPT_RESOLVE, [$host.':'.$port.':'.implode(',', array_map(function($ip){
                    return strpos($ip, ':') !== false ? '['.$ip.']' : $ip;
                }, $ips))]);
            }

            if($headers)
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if($method == 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            } elseif($method != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $error_description = curl_error($ch);
            $connected_ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            curl_close($ch);

            // In case curl ended up somewhere other than the address that was checked
            if($connected_ip && !env('ALLOW_PRIVATE_NETWORK_REQUESTS') && !self::is_public_ip($connected_ip))
                return self::error_response($url, 'connect_error', 'Requests to private network addresses are not allowed');

            if($errno)
                return self::error_response($url, \p3k\HTTP\Curl::error_string_from_code($errno), $error_description, $code);

            $header = trim(substr($response, 0, $header_size));

            if(in_array($code, [301, 302, 303, 307, 308]) && preg_match('/^Location:\s*(.+)$/mi', $header, $match)) {
                if($redirects >= $this->_max_redirects)
                    return self::error_response($url, 'too_many_redirects', 'Too many redirects', $code);

                $url = \Mf2\resolveUrl($url, trim($match[1]));
                if($code == 303 && $method != 'HEAD') {
                    $method = 'GET';
                    $body = null;
                }
                continue;
            }

            return [
                'code' => $code,
                'header' => $header,
                'body' => $method == 'HEAD' ? false : substr($response, $header_size),
                'error' => '',
                'error_description' => '',
                'url' => $url,
                'debug' => '',
            ];
        }
    }

    // Returns [error, host, port, ips], where error is null or [code, description]
    public static function check_url($url) {
        // Refuse URLs that parsers might read differently, such as ones with a backslash or
        // user info, so the host checked here is the host curl connects to
        if(!is_string($url) || preg_match('/[\\\\\s\x00-\x1f\x7f]/', $url))
            return [['invalid_url', 'The URL provided was not valid'], null, null, []];

        $parts = parse_url($url);
        if($parts === false || isset($parts['user']) || isset($parts['pass']))
            return [['invalid_url', 'The URL provided was not valid'], null, null, []];

        $scheme = strtolower($parts['scheme'] ?? '');
        if(!in_array($scheme, ['http', 'https']) || empty($parts['host']))
            return [['invalid_url', 'Only http and https URLs are supported'], null, null, []];

        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme == 'https' ? 443 : 80);

        // IPv6 literals are written in brackets in URLs
        $literal = trim($host, '[]');
        if(filter_var($literal, FILTER_VALIDATE_IP)) {
            $ips = [$literal];
        } else {
            $ips = self::resolve($host);
            if(!$ips)
                return [['dns_error', 'Could not resolve '.$host], $host, $port, []];
        }

        if(!env('ALLOW_PRIVATE_NETWORK_REQUESTS')) {
            foreach($ips as $ip) {
                if(!self::is_public_ip($ip))
                    return [['connect_error', 'Requests to private network addresses are not allowed'], $host, $port, $ips];
            }
        }

        return [null, $host, $port, $ips];
    }

    public static function resolve($host) {
        $ips = gethostbynamel($host) ?: [];

        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach($records as $record) {
            if(isset($record['ipv6']))
                $ips[] = $record['ipv6'];
        }

        return array_values(array_unique($ips));
    }

    public static function is_public_ip($ip) {
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
            return false;

        foreach(self::BLOCKED_RANGES as $range) {
            if(self::ip_in_range($ip, $range))
                return false;
        }

        return true;
    }

    public static function ip_in_range($ip, $range) {
        list($subnet, $bits) = explode('/', $range);

        $ip = @inet_pton($ip);
        $subnet = @inet_pton($subnet);
        if($ip === false || $subnet === false || strlen($ip) != strlen($subnet))
            return false;

        $bytes = intdiv((int)$bits, 8);
        $remainder = $bits % 8;

        if(substr($ip, 0, $bytes) !== substr($subnet, 0, $bytes))
            return false;

        if($remainder == 0)
            return true;

        $mask = chr((0xff << (8 - $remainder)) & 0xff);
        return (ord($ip[$bytes]) & ord($mask)) == (ord($subnet[$bytes]) & ord($mask));
    }

    private static function error_response($url, $error, $description, $code=0) {
        return [
            'code' => $code,
            'header' => '',
            'body' => '',
            'error' => $error,
            'error_description' => $description,
            'url' => $url,
            'debug' => '',
        ];
    }

}
