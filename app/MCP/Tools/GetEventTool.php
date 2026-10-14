<?php

namespace App\MCP\Tools;

use App\MCP\MCPTool;
use App\Event;

class GetEventTool implements MCPTool
{
    public function name(): string { return 'get_event'; }

    public function title(): string { return 'Get Event'; }

    public function description(): string
    {
        return 'Get full details for a single event by its key (short ID). '
            . 'Includes description, links, and RSVP counts.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                    'description' => 'The event key (short alphanumeric ID visible in the event URL).',
                ],
            ],
            'required' => ['key'],
        ];
    }

    public function call(array $args): array
    {
        $key = trim($args['key'] ?? '');
        if (!$key) {
            return [
                'content' => [['type' => 'text', 'text' => 'Missing required parameter: key']],
                'isError' => true,
            ];
        }

        $event = Event::where('key', $key)
            ->where('unlisted', 0)
            ->with('tags')
            ->first();

        if (!$event) {
            return [
                'content' => [['type' => 'text', 'text' => "Event not found: {$key}"]],
                'isError' => true,
            ];
        }

        $data = ListEventsTool::formatEvent($event);
        $data['description']     = $event->description;
        $data['website']         = $event->website;
        $data['tickets_url']     = $event->tickets_url;
        $data['meeting_url']     = $event->meeting_url_is_visible() ? $event->meeting_url : null;
        $data['meeting_state']   = $event->meeting_state();
        $data['video_url']       = $event->video_url;
        $data['rsvp_counts']     = [
            'yes'    => $event->rsvps_yes()->count(),
            'no'     => $event->rsvps_no()->count(),
            'maybe'  => $event->rsvps_maybe()->count(),
            'remote' => $event->rsvps_remote()->count(),
        ];

        // The candidate dates of a proposed event and how the voting stands
        if ($event->is_proposed) {
            $data['date_options'] = $event->date_options_with_tallies()->map(function ($option) {
                return [
                    'date'       => $option->date,
                    'end_date'   => $option->end_date,
                    'start_time' => $option->start_time,
                    'end_time'   => $option->end_time,
                    'votes'      => $option->vote_counts(),
                ];
            })->values()->all();
        }

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]],
            'isError' => false,
        ];
    }
}
