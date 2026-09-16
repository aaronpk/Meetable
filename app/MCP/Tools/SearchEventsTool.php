<?php

namespace App\MCP\Tools;

use App\MCP\MCPTool;
use App\Event;

class SearchEventsTool implements MCPTool
{
    public function name(): string { return 'search_events'; }

    public function title(): string { return 'Search Events'; }

    public function description(): string
    {
        return 'Search for events by keyword across event names, summaries, and descriptions. '
            . 'Returns both past and upcoming matching events, ordered by date (newest first).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Keyword or phrase to search for.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return. Default 10, max 50.',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function call(array $args): array
    {
        $query = trim($args['query'] ?? '');
        if (!$query) {
            return [
                'content' => [['type' => 'text', 'text' => 'Missing required parameter: query']],
                'isError' => true,
            ];
        }

        $limit = min((int)($args['limit'] ?? 10), 50);
        $limit = max(1, $limit);

        $like = '%' . $query . '%';

        $events = Event::where('unlisted', 0)
            ->where('is_template', 0)
            ->where('is_proposed', 0)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('summary', 'like', $like)
                  ->orWhere('description', 'like', $like);
            })
            ->orderBy('sort_date', 'desc')
            ->with('tags')
            ->limit($limit)
            ->get();

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode(
                    array_values(array_map([ListEventsTool::class, 'formatEvent'], $events->all())),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ),
            ]],
            'isError' => false,
        ];
    }
}
