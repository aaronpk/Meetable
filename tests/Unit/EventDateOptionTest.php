<?php

namespace Tests\Unit;

use App\Event;
use App\EventDateOption;
use Tests\TestCase;

class EventDateOptionTest extends TestCase
{
    public function testADateWithoutTimesIsShownLikeAnAllDayEvent()
    {
        $option = new EventDateOption;
        $option->date = '2032-10-06';

        $this->assertEquals('Wednesday, October 6, 2032', $option->display_date());
        $this->assertEquals('October 6, 2032', $option->display_date(false));
        $this->assertEquals('', $option->display_time());
    }

    public function testADateWithTimesUsesTheEventsTimezone()
    {
        $event = new Event;
        $event->timezone = 'America/Los_Angeles';

        $option = new EventDateOption;
        $option->date = '2032-10-08';
        $option->start_time = '18:00:00';
        $option->end_time = '20:00:00';
        $option->setRelation('event', $event);

        $this->assertEquals('6:00 - 8:00pm', $option->display_time());
        $this->assertEquals('2032-10-08T18:00:00-07:00', $option->start_datetime()->format('c'));
        $this->assertEquals('2032-10-08T20:00:00-07:00', $option->end_datetime()->format('c'));
    }

    public function testADateWithAnEndDateIsShownAsARange()
    {
        $option = new EventDateOption;
        $option->date = '2032-10-06';
        $option->end_date = '2032-10-08';

        $this->assertTrue($option->is_multiday());
        $this->assertEquals('October 6 - 8, 2032', $option->display_date(false));
        $this->assertEquals('Wednesday, October 6 - Friday, October 8, 2032', $option->display_date());
    }

    public function testVoteCountsComeFromTheLoadedTallies()
    {
        $option = new EventDateOption;
        $option->yes_count = 3;
        $option->ifneedbe_count = 1;
        $option->no_count = 0;

        $this->assertEquals(['yes' => 3, 'ifneedbe' => 1, 'no' => 0], $option->vote_counts());
    }
}
