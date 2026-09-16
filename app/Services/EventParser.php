<?php
namespace App\Services;

use App\Event, App\Tag;
use ICal\ICal;
use DateTime, DateTimeZone, DateInterval;

class EventParser {

    public static function eventFromURL($url) {

        $response = static::_fetch($url);

        $data = json_decode($response, true);

        if($data && is_array($data) && isset($data['generator']) && $data['generator'] == 'Meetable') {
            // Check for importing an event from another Meetable instance

            $event_data = $data['event'];

            $event = new Event;

            foreach(Event::$EDITABLE_PROPERTIES as $p) {
                $event->{$p} = $event_data[$p] ?? '';
            }

            $event->temp_tag_string = implode(' ', $event_data['tag_list']);

            return $event;

        } elseif(substr($response, 0, 15) == 'BEGIN:VCALENDAR') {
            // Check for ICS feed

            return self::eventFromICS($response);

        } else {
            // Parse using XRay to find Microformats event markup

            $xray = \App\Helpers\SafeHTTP::xray();
            $data = $xray->parse($url, $response);

            if(isset($data['data']['type']) && $data['data']['type'] == 'event') {

                $info = $data['data'];

                $event = new Event;

                if(isset($info['name']))
                    $event->name = $info['name'];

                if(isset($info['location'])) {
                    $map = [
                        'name' => 'location_name',
                        'street-address' => 'location_address',
                        'locality' => 'location_locality',
                        'region' => 'location_region',
                        'country-name' => 'country',
                        'latitude' => 'latitude',
                        'longitude' => 'longitude',
                    ];
                    foreach($map as $mf=>$db) {
                        if(isset($info['location'][$mf]))
                            $event->{$db} = $info['location'][$mf];
                    }
                }

                $time_regex = '/([0-9]{4}-[0-9]{2}-[0-9]{2})[ T]?(?:([0-9]{2}:[0-9]{2}:[0-9]{2})([-+][0-9]{2}:?[0-9]{2})?)?/';

                if(isset($info['start'])) {
                    if(preg_match($time_regex, $info['start'], $match)) {
                        if(isset($match[1]))
                            $event->start_date = $match[1];
                        if(isset($match[2]))
                            $event->start_time = $match[2];
                        if(isset($match[3]))
                            $event->timezone = Event::timezone_name_from_offset($match[3], new DateTime($event->start_date));
                    }
                }

                if(isset($info['end'])) {
                    if(preg_match($time_regex, $info['end'], $match)) {
                        if(isset($match[1]) && $event->start_date != $match[1])
                            $event->end_date = $match[1];
                        if(isset($match[2]))
                            $event->end_time = $match[2];
                    }
                }

                if(isset($data['rels']['canonical']))
                    $event->website = $data['rels']['canonical'];

                if(isset($info['content']['html']))
                    $event->description = $info['content']['html'];
                elseif(isset($info['content']['text']))
                    $event->description = $info['content']['text'];

                if(isset($info['category'])) {
                    $tags = [];
                    foreach($info['category'] as $category) {
                        $tags[] = Tag::normalize($category);
                    }
                    $event->temp_tag_string = implode(' ', $tags);
                }

                return $event;
            } else {
                return null;
            }
        }
    }

    // Build an event from an ICS feed. Only single events are supported, so a
    // recurrence rule is left alone rather than being expanded into its instances.
    public static function eventFromICS($ics) {

        try {
            $ical = new ICal(false, [
                'defaultTimeZone' => 'UTC',
                'skipRecurrence' => true,
            ]);
            $ical->initString($ics);
            $ics_events = $ical->events();
        } catch(\Exception $e) {
            return null;
        }

        if(!$ics_events)
            return null;

        // A feed can describe more than one event, so import the one that starts first
        usort($ics_events, function($a, $b) {
            return ($a->dtstart_array[2] ?? 0) <=> ($b->dtstart_array[2] ?? 0);
        });
        $ics_event = $ics_events[0];

        $event = new Event;

        $event->name = $ics_event->summary ?: '';

        if($ics_event->description)
            $event->description = $ics_event->description;

        if($ics_event->location)
            $event->location_name = $ics_event->location;

        if($ics_event->url)
            $event->website = $ics_event->url;

        // The ICS statuses are a subset of the ones Meetable knows about
        if($ics_event->status && isset(Event::$STATUSES[strtolower($ics_event->status)]))
            $event->status = strtolower($ics_event->status);

        if($ics_event->categories) {
            $tags = [];
            foreach(explode(',', $ics_event->categories) as $category) {
                if(($tag = Tag::normalize(trim($category))))
                    $tags[] = $tag;
            }
            if($tags)
                $event->temp_tag_string = implode(' ', $tags);
        }

        self::_setICSDates($event, $ical, $ics_event);

        if(self::_isIETFCalendar($ical))
            self::_applyIETFDetails($event);

        return $event;
    }

    private static function _setICSDates(Event $event, ICal $ical, $ics_event) {

        if(!isset($ics_event->dtstart_array[2]))
            return;

        $timezone = self::_icsTimezone($ical, $ics_event);

        // Floating times belong to no timezone, so they were read as UTC above and
        // are written back out as UTC to recover the wall clock time as written
        $display_timezone = new DateTimeZone($timezone ?: 'UTC');

        $all_day = self::_icsIsAllDay($ics_event);

        $start = (new DateTime('@'.$ics_event->dtstart_array[2]))->setTimezone($display_timezone);

        $event->start_date = $start->format('Y-m-d');

        if(!$all_day) {
            $event->start_time = $start->format('H:i:00');
            if($timezone)
                $event->timezone = $timezone;
        }

        if(!($end = self::_icsEnd($ics_event)))
            return;

        $end->setTimezone($display_timezone);

        if($all_day) {
            // The end date of an all-day event is exclusive, matching the ICS this app
            // generates, so the last day of the event is the day before it
            $end->modify('-1 day');
        } else {
            $event->end_time = $end->format('H:i:00');
        }

        $end_date = $end->format('Y-m-d');

        if($end_date > $event->start_date && !self::_icsCrossesMidnight($event, $end_date))
            $event->end_date = $end_date;
    }

    // An event that runs past midnight is stored with only an end time, since the
    // model already reads an end time before the start time as the following day.
    // Storing an end date instead would turn it into a multi-day event.
    private static function _icsCrossesMidnight(Event $event, $end_date) {
        return $event->start_time && $event->end_time
            && $event->end_time < $event->start_time
            && $end_date == date('Y-m-d', strtotime($event->start_date.' +1 day'));
    }

    private static function _icsEnd($ics_event) {
        if(isset($ics_event->dtend_array[2]))
            return new DateTime('@'.$ics_event->dtend_array[2]);

        // An event can give its length as a duration instead of an end date
        if(isset($ics_event->duration_array[2]) && $ics_event->duration_array[2] instanceof DateInterval)
            return (new DateTime('@'.$ics_event->dtstart_array[2]))->add($ics_event->duration_array[2]);

        return null;
    }

    private static function _icsIsAllDay($ics_event) {
        if(isset($ics_event->dtstart_array[0]['VALUE']) && $ics_event->dtstart_array[0]['VALUE'] == 'DATE')
            return true;

        // A date with no time part is a date, whether or not it says so
        return isset($ics_event->dtstart_array[1]) && strpos($ics_event->dtstart_array[1], 'T') === false;
    }

    private static function _icsTimezone(ICal $ical, $ics_event) {

        // An explicit timezone on the start date wins
        if(($timezone = self::_icsValidTimezone($ical, $ics_event->dtstart_array[0]['TZID'] ?? null)))
            return $timezone;

        if(substr($ics_event->dtstart_array[1] ?? '', -1) == 'Z') {
            // A UTC time doesn't say which timezone the event is actually held in, so
            // look for a hint from the event or the calendar before settling for UTC
            if(($timezone = self::_icsValidTimezone($ical, $ics_event->x_meeting_tz)))
                return $timezone;

            if(($timezone = self::_icsValidTimezone($ical, $ical->calendarTimeZone(true))))
                return $timezone;

            return 'UTC';
        }

        // Floating times have no timezone
        return null;
    }

    private static function _icsValidTimezone(ICal $ical, $timezone) {
        if(!$timezone || !is_string($timezone))
            return null;

        try {
            // This resolves quoted, CLDR and Windows timezone names to an IANA name
            $timezone = $ical->timeZoneStringToDateTimeZone($timezone)->getName();
        } catch(\Exception $e) {
            return null;
        }

        // The timezone has to be one the event form can offer in its menu
        return in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL)) ? $timezone : null;
    }

    protected static function _fetch($url) {
        return \App\Helpers\SafeHTTP::fetch($url);
    }

    private static function _isIETFCalendar(ICal $ical) {
        return stripos($ical->cal['VCALENDAR']['PRODID'] ?? '', 'datatracker.ietf.org') !== false;
    }

    // The IETF datatracker packs a list of links into the event description. The
    // remote participation link belongs in its own field, the session materials link
    // is already the website, and the agenda those links point at makes a much better
    // description than the list of links itself.
    private static function _applyIETFDetails(Event $event) {

        if(!$event->description)
            return;

        $agenda_url = null;

        foreach(explode("\n", $event->description) as $line) {
            $line = trim($line);

            if(preg_match('~^Remote instructions:\s*(https?://\S+)$~i', $line, $match))
                $event->meeting_url = $match[1];
            elseif(preg_match('~^Agenda:?\s+(https?://\S+)$~i', $line, $match))
                $agenda_url = $match[1];
        }

        if(!$agenda_url)
            return;

        // Keep the description the feed provided if the agenda can't be fetched
        if(!($agenda = static::_fetch($agenda_url)))
            return;

        $event->description = self::_agendaToDescription($agenda);
    }

    private static function _agendaToDescription($agenda) {
        // An agenda is uploaded either as an HTML document or as plain text. The
        // markup of an HTML one already links its URLs, so only the body is needed.
        if(preg_match('~<body[^>]*>(.*)</body>~is', $agenda, $match))
            return trim($match[1]);

        return self::_preserveLineBreaks(self::_autolink(trim($agenda)));
    }

    // Markdown folds consecutive lines into a single paragraph, so every line that is
    // followed by another gets the two trailing spaces that force a line break
    private static function _preserveLineBreaks($text) {
        $lines = explode("\n", str_replace("\r\n", "\n", $text));

        foreach($lines as $i => $line) {
            $line = rtrim($line);

            // A blank line already separates paragraphs on its own
            if($line !== '' && isset($lines[$i+1]) && trim($lines[$i+1]) !== '')
                $line .= '  ';

            $lines[$i] = $line;
        }

        return implode("\n", $lines);
    }

    // Markdown only turns a bare URL into a link once it is wrapped in angle brackets
    private static function _autolink($text) {
        return preg_replace_callback('~(?<![<(\w])https?://[^\s<>]+~', function($match) {
            $url = $match[0];

            // Punctuation ending the sentence is not part of the link
            $trailing = '';
            while($url !== '' && strpos('.,;:!?', substr($url, -1)) !== false) {
                $trailing = substr($url, -1).$trailing;
                $url = substr($url, 0, -1);
            }

            return '<'.$url.'>'.$trailing;
        }, $text);
    }

}
