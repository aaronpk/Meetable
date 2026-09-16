<?php
namespace App\Helpers;

use p3k\HTTP;
use p3k\HTTP\Transport;

/**
 * XRay swaps in its own curl or stream transport before every fetch. This keeps
 * SafeHTTP in place instead, so everything XRay fetches goes through it.
 */
class SafeXRayHTTP extends HTTP {

    public function __construct(HTTP $original) {
        // Keep the user agent XRay chose
        $user_agent = (new \ReflectionProperty(HTTP::class, '_user_agent'))->getValue($original);

        parent::__construct($user_agent, new SafeHTTP());

        $this->set_timeout($original->_timeout);
        $this->set_max_redirects($original->_max_redirects);
    }

    public function set_transport(Transport $transport) {
        if($transport instanceof SafeHTTP)
            parent::set_transport($transport);
    }

}
