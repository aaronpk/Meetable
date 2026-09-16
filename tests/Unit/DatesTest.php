<?php

namespace Tests\Unit;

use App\Helpers\Dates;
use DateTime;
use DateTimeZone;
use Tests\TestCase;

class DatesTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->setLocale('en');

        parent::tearDown();
    }

    public function testEnglishFormatsMatchTheOriginalPhpFormats()
    {
        $date = new DateTime('2031-06-03 18:30:00', new DateTimeZone('America/Los_Angeles'));

        $this->assertEquals($date->format('M j, Y'), Dates::format($date, 'date'));
        $this->assertEquals($date->format('F j, Y'), Dates::format($date, 'date_long'));
        $this->assertEquals($date->format('l, F j, Y'), Dates::format($date, 'date_full'));
        $this->assertEquals($date->format('M j'), Dates::format($date, 'month_day'));
        $this->assertEquals($date->format('j, Y'), Dates::format($date, 'day_year'));
        $this->assertEquals($date->format('F Y'), Dates::format($date, 'month_year'));
        $this->assertEquals($date->format('D'), Dates::format($date, 'weekday_short'));
        $this->assertEquals($date->format('g:ia'), Dates::format($date, 'time'));
        $this->assertEquals($date->format('g:i'), Dates::format($date, 'time_no_meridiem'));
        $this->assertEquals($date->format('M j, Y g:ia'), Dates::format($date, 'datetime'));
        $this->assertEquals($date->format('D g:ia'), Dates::format($date, 'weekday_time'));
        $this->assertEquals('3rd', Dates::format($date, 'day_ordinal'));
    }

    public function testTheTimezoneOfADateTimeIsKept()
    {
        $date = new DateTime('2031-06-03 23:30:00', new DateTimeZone('America/Los_Angeles'));

        $this->assertEquals('Jun 3, 2031 11:30pm', Dates::format($date, 'datetime'));
        $this->assertEquals('Jun 3, 2031', Dates::format('2031-06-03', 'date'));
        $this->assertEquals(date('M j, Y', 1970000000), Dates::format(1970000000, 'date'));
    }

    public function testMonthAndDayNamesFollowTheLocale()
    {
        app()->setLocale('fr');

        $date = new DateTime('2031-06-03 09:05:00');

        $this->assertStringContainsString('juin', Dates::format($date, 'date_long'));
        $this->assertStringContainsString('mardi', Dates::format($date, 'weekday'));
    }
}
