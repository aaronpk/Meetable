<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\HerokuS3;

class HerokuS3HelperTest extends TestCase
{
    // Preserve Environment
    protected $_oldenv;

    protected function setUp() : void
    {
        $this->_oldenv = $_ENV;
    }

    protected function tearDown() : void
    {
        $_ENV = $this->_oldenv;
    }

    /**
     * @test
     * @dataProvider systemTestProvider
     */
    public function get_aws_bucket_from_env($system_env, $expectations) {
        $this->restoreEnv($system_env);

        $this->assertEquals(
            $expectations['bucket'],
            HerokuS3::get_aws_bucket_from_env()
        );
    }

    /**
     * @test
     * @dataProvider systemTestProvider
     */
    public function get_aws_root_from_env($system_env, $expectations) {
        $this->restoreEnv($system_env);

        $this->assertEquals(
            $expectations['root'],
            HerokuS3::get_aws_root_from_env()
        );
    }

    /**
     * @test
     * @dataProvider systemTestProvider
     */
    public function get_aws_url_from_env($system_env, $expectations) {
        $this->restoreEnv($system_env);

        $this->assertEquals(
            $expectations['aws_url'],
            HerokuS3::get_aws_url_from_env()
        );
    }

    /**
     * @test
     * @dataProvider systemTestProvider
     */
    public function get_default_aws_region($system_env, $expectations) {
        $this->restoreEnv($system_env);

        $this->assertEquals(
            $expectations['aws_region'],
            HerokuS3::get_default_aws_region()
        );
    }

    /**
     * @test
     * @dataProvider cloudcubeTestProvider
     */
    public function get_default_cloudcube_region(
        $left_most_subdomain, $expectation
    ) {
        $this->assertEquals(
            $expectation,
            HerokuS3::get_default_cloudcube_region(
                $left_most_subdomain
            )
        );
    }

    private function restoreEnv($system_env) {
        $_ENV = $system_env;
    }

    public static function cloudcubeTestProvider() {
        return [
            "Generally (default)" => [
                'Anything You Want',
                null
            ],
            "US east 1" => [
                'cloud-cube',
                'us-east-1'
            ],
            "EU west 1" => [
                'cloud-cube-eu',
                'eu-west-1'
            ],
            "AP NorthEast 1" => [
                'cloud-cube-jp',
                'ap-northeast-1'
            ]
        ];
    }

    public static function systemTestProvider() {
        return [
            "When CLOUDCUBE_URL is not set" => [
                'ENV' => [],
                'expectations'=> [
                    'bucket' => null,
                    'aws_url' => null,
                    'aws_region' => null,
                    'root' => null,
                ]
            ],
            "When CLOUDCUBE_URL is an US one" => [
                'ENV' => [
                    'CLOUDCUBE_URL' => 'https://cloud-cube.s3.amazonaws.com/xmfnhr2po8rp'
                ],
                'expectations'=> [
                    'bucket' => 'cloud-cube',
                    'aws_url' => 'https://cloud-cube.s3.amazonaws.com',
                    'aws_region' => 'us-east-1',
                    'root' => 'xmfnhr2po8rp'
                ]
            ],
            "When CLOUDCUBE_URL is an EU one" => [
                'ENV' => [
                    'CLOUDCUBE_URL' => 'https://cloud-cube-eu.s3.amazonaws.com/my-apps-root'
                ],
                'expectations'=> [
                    'bucket' => 'cloud-cube-eu',
                    'aws_url' => 'https://cloud-cube-eu.s3.amazonaws.com',
                    'aws_region' => 'eu-west-1',
                    'root' => 'my-apps-root',
                ]
            ],
            "When CLOUDCUBE_URL is an AP one" => [
                'ENV' => [
                    'CLOUDCUBE_URL' => 'https://cloud-cube-jp.s3.amazonaws.com/something'
                ],
                'expectations'=> [
                    'bucket' => 'cloud-cube-jp',
                    'aws_url' => 'https://cloud-cube-jp.s3.amazonaws.com',
                    'aws_region' => 'ap-northeast-1',
                    'root' => 'something',
                ]
            ]
        ];
    }
}
