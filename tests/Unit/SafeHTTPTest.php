<?php

namespace Tests\Unit;

use App\Helpers\SafeHTTP;
use App\Helpers\SafeXRayHTTP;
use Tests\TestCase;

class SafeHTTPTest extends TestCase
{
    private function refused($url)
    {
        list($error) = SafeHTTP::check_url($url);
        return $error !== null;
    }

    public function testOnlyHttpAndHttpsAreAllowed()
    {
        foreach(['file:///etc/passwd', 'gopher://example.com:6379/_INFO', 'dict://example.com:11211/stat', 'ftp://example.com/', 'javascript:alert(1)', '/relative/path', ''] as $url) {
            $this->assertTrue($this->refused($url), $url.' should be refused');
        }
    }

    public function testPrivateAndReservedAddressesAreRefused()
    {
        $urls = [
            'http://127.0.0.1/',
            'http://127.1.2.3:6379/',
            'http://10.0.0.1/',
            'http://172.16.5.4/',
            'http://192.168.1.1/',
            'http://169.254.169.254/latest/meta-data/',
            'http://100.64.0.1/',
            'http://0.0.0.0/',
            'http://224.0.0.1/',
            'http://[::1]/',
            'http://[::ffff:127.0.0.1]/',
            'http://[fd00::1]/',
            'http://[fe80::1]/',
            'http://localhost/',
        ];
        foreach($urls as $url) {
            $this->assertTrue($this->refused($url), $url.' should be refused');
        }
    }

    public function testUrlsThatParsersMightReadDifferentlyAreRefused()
    {
        $this->assertTrue($this->refused('http://example.com\\@127.0.0.1/'));
        $this->assertTrue($this->refused('http://user:pass@example.com/'));
        $this->assertTrue($this->refused("http://example.com/\r\nHost: 127.0.0.1"));
        $this->assertTrue($this->refused('http://example.com /'));
    }

    public function testPublicAddressesAreAllowed()
    {
        $this->assertFalse($this->refused('http://93.184.215.14/'));
        $this->assertFalse($this->refused('https://[2606:4700:4700::1111]/'));
        $this->assertFalse($this->refused('https://93.184.215.14/search?q=s'));
    }

    public function testIpRanges()
    {
        $this->assertTrue(SafeHTTP::ip_in_range('100.127.255.255', '100.64.0.0/10'));
        $this->assertFalse(SafeHTTP::ip_in_range('100.128.0.0', '100.64.0.0/10'));
        $this->assertTrue(SafeHTTP::ip_in_range('::ffff:10.0.0.1', '::ffff:0:0/96'));
        $this->assertFalse(SafeHTTP::ip_in_range('10.0.0.1', 'fc00::/7'));

        $this->assertTrue(SafeHTTP::is_public_ip('8.8.8.8'));
        $this->assertFalse(SafeHTTP::is_public_ip('192.0.0.8'));
        $this->assertFalse(SafeHTTP::is_public_ip('2002:7f00:1::'));
    }

    public function testRequestsToPrivateAddressesAreNotMade()
    {
        $response = (new SafeHTTP)->get('http://127.0.0.1:1/');

        $this->assertEquals('connect_error', $response['error']);
        $this->assertEquals('', $response['body']);
        $this->assertNull(SafeHTTP::fetch('http://169.254.169.254/latest/meta-data/'));
        $this->assertNull(SafeHTTP::fetch_image('file:///etc/passwd'));
    }

    public function testXRayCannotSwapInItsOwnTransport()
    {
        $xray = SafeHTTP::xray();
        $this->assertInstanceOf(SafeXRayHTTP::class, $xray->http);

        $data = $xray->parse('http://127.0.0.1/');
        $this->assertEquals('connect_error', $data['error']);
        $this->assertEquals('The source URL could not be fetched', SafeHTTP::xray_error_description($data));

        $data = $xray->parse('file:///etc/passwd');
        $this->assertEquals('invalid_url', $data['error']);
    }
}
