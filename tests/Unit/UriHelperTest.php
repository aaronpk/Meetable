<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\Uri;

class UriHelperTest extends TestCase
{
    /**
     * @test
     * @dataProvider uriTestProvider
     */
    public function get_uri_path($uri, $expectations)
    {
        $output = Uri::get_uri_path($uri);
        $this->assertEquals($expectations['path'], $output);
    }

    /**
     * @test
     * @dataProvider uriTestProvider
     */
    public function get_uri_without_path($uri, $expectations) {
        $output = Uri::get_uri_without_path(
            $uri, Uri::get_uri_path($uri)
        );
        $this->assertEquals($expectations['uri_sans_path'], $output);
    }

    /**
     * @test
     * @dataProvider uriTestProvider
     */
    public function get_uri_host($uri, $expectations) {
        $output = Uri::get_uri_host($uri);
        $this->assertEquals($expectations['host'], $output);
    }

    /**
     * @test
     * @dataProvider uriTestProvider
     */
    public function get_uri_domain_parts($uri, $expectations) {
        $output = Uri::get_uri_domain_parts(
            Uri::get_uri_host($uri)
        );
        $this->assertEquals($expectations['host_parts'], $output);
        $this->assertEquals(count($expectations['host_parts']), count($output));
    }

    public static function uriTestProvider() {
        return [
            "When Uri is Null" => [
                "uri" => null,
                "expectations" => [
                    "path" => "",
                    "uri_sans_path" => "",
                    "host" => "",
                    "host_parts" => []
                ]
            ],
            "When Uri without path" => [
                "uri" => 'https://www.google.com',
                "expectations" => [
                    "path" => "",
                    "uri_sans_path" => "https://www.google.com",
                    "host" => "www.google.com",
                    "host_parts" => ["www", "google", "com"]
                ]
            ],
            "When Uri Path is Root" => [
                "uri" => 'https://www.google.com/',
                "expectations" => [
                    "path" => "/",
                    "uri_sans_path" => "https://www.google.com",
                    "host" => "www.google.com",
                    "host_parts" => ["www", "google", "com"]
                ]
            ],
            "When given Uri with path" => [
                "uri" => 'https://cloud-cube.s3.amazonaws.com/xmfnhr2po8rp',
                "expectations" => [
                    "path" => "/xmfnhr2po8rp",
                    "uri_sans_path" => "https://cloud-cube.s3.amazonaws.com",
                    "host" => "cloud-cube.s3.amazonaws.com",
                    "host_parts" => ["cloud-cube", "s3", "amazonaws", "com"]
                ]
            ]
        ];
    }
}
