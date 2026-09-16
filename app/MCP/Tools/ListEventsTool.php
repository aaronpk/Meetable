<?php

namespace App\MCP\Tools;

use App\MCP\MCPTool;
use App\Event;
use App\Tag;
use DateTime, DateTimeZone;

class ListEventsTool implements MCPTool
{
    public function name(): string { return 'list_events'; }

    public function title(): string { return 'List Events'; }

    public function description(): string
    {
        return 'List events from the calendar, optionally filtered by date range and/or tags. '
            . 'Returns upcoming events by default (from today onwards). '
            . 'Excludes unlisted events.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Start of date range, YYYY-MM-DD format. Defaults to today.',
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'End of date range, YYYY-MM-DD format. Optional.',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Filter to events with any of these tags.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of events to return. Default 20, max 100.',
                ],
            ],
        ];
    }

    public function call(array $args): array
    {
        $limit = min((int)($args['limit'] ?? 20), 100);
        $limit = max(1, $limit);

        $now = new DateTime('now', new DateTimeZone('-12:00'));
        $startDate = $args['start_date'] ?? $now->format('Y-m-d');

        $query = Event::where('start_date', '>=', $startDate)
            ->where('unlisted', 0)
            ->where('hide_from_main_feed', 0)
            ->where('is_template', 0)
            ->where('is_proposed', 0)
            ->orderBy('sort_date');

        if (!empty($args['end_date'])) {
            $query->where('start_date', '<=', $args['end_date']);
        }

        if (!empty($args['tags'])) {
            $tagNames = array_map('strval', $args['tags']);
            $tagObjects = Tag::whereIn('tag', $tagNames)->get()->all();
            if ($tagObjects) {
                $query = Event::tagged($query, $tagObjects);
            }
        }

        $events = $query->with('tags')->limit($limit)->get();

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($this->formatEvents($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]],
            'isError' => false,
        ];
    }

    public static function formatEvent(Event $event): array
    {
        return [
            'key'               => $event->key,
            'name'              => $event->name,
            'status'            => $event->status,
            'start_date'        => $event->start_date,
            'start_time'        => $event->start_time,
            'end_date'          => $event->end_date,
            'end_time'          => $event->end_time,
            'timezone'          => $event->timezone,
            'location_name'     => $event->location_name,
            'location_locality' => $event->location_locality,
            'location_region'   => $event->location_region,
            'location_country'  => $event->location_country,
            'summary'           => $event->summary,
            'tags'              => $event->tag_list,
            'url'               => $event->absolute_permalink(),
        ];
    }

    private function formatEvents($events): array
    {
        return array_values(array_map([self::class, 'formatEvent'], $events->all()));
    }
}
