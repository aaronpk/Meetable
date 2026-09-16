<?php

namespace Tests\Unit;

use App\Http\Controllers\Setup\Controller as SetupController;
use Dotenv\Dotenv;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class SetupConfigTest extends TestCase
{
    private function writeConfig($config, $key, $value)
    {
        $method = new \ReflectionMethod(SetupController::class, 'write_config_value');
        $method->invokeArgs(null, [&$config, $key, $value]);
        return $config;
    }

    public function testValuesCannotAddLinesToTheEnvFile()
    {
        $config = "APP_NAME=Meetable\nDB_PASSWORD=\nAUTH_METHOD=session\n# APP_DEBUG=false\n";

        $config = $this->writeConfig($config, 'DB_PASSWORD', "secret\nAUTH_METHOD=vouch\nAPP_DEBUG=true");

        $values = Dotenv::parse($config);
        $this->assertEquals('session', $values['AUTH_METHOD']);
        $this->assertArrayNotHasKey('APP_DEBUG', $values);
        $this->assertEquals('secretAUTH_METHOD=vouchAPP_DEBUG=true', $values['DB_PASSWORD']);
    }

    public function testValuesAreReadBackExactly()
    {
        $values = [
            'simple' => 'plain-value_1.2',
            'spaces' => 'My Events Site',
            'dollar backreference' => 'pa$1ss\\1word',
            'variable' => 'abc${APP_NAME}def',
            'quotes' => 'it\'s "quoted" \\ here $HOME',
            'hash' => 'value # not a comment',
        ];

        foreach($values as $label => $value) {
            $config = $this->writeConfig("APP_NAME=Meetable\n# DB_PASSWORD=\n", 'DB_PASSWORD', $value);
            $this->assertEquals($value, Dotenv::parse($config)['DB_PASSWORD'], $label);
            $this->assertEquals('Meetable', Dotenv::parse($config)['APP_NAME'], $label);
        }
    }

    public function testJsonSessionsDoNotUnserializeObjects()
    {
        $handler = new ArraySessionHandler(10);
        $handler->write('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN', serialize(['user' => new \ArrayObject(['x' => 1])]));

        $session = new Store('test', $handler, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN', 'json');
        $session->start();

        $this->assertNull($session->get('user'));
    }
}
