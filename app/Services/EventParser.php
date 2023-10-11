<?php
namespace App\Services;

use App\Event, App\Tag;
use p3k\XRay;
use DateTime;

class EventParser {

    public static function eventFromURL($url) {

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);

        $data = json_decode($response, true);

        if($data && is_array($data) && isset($data['generator']) && $data['generator'] == 'Meetable') {

            $event_data = $data['event'];

            $event = new Event;

            foreach(Event::$EDITABLE_PROPERTIES as $p) {
                $event->{$p} = $event_data[$p] ?? '';
            }

            $event->temp_tag_string = implode(' ', $event_data['tag_list']);

            return $event;

        } else {

            $xray = new XRay;
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

}
