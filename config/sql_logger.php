<?php

return [

    'general' => [
        /*
         * Directory where log files will be saved
         */
        'directory' => storage_path(env('SQL_LOGGER_DIRECTORY', 'logs/sql')),

        /*
         * Whether execution time in log file should be displayed in seconds
         * (by default it's in milliseconds)
         */
        'use_seconds' => env('SQL_LOGGER_USE_SECONDS', false),

        /*
         * Suffix for Artisan queries logs (if it's empty same files will be used for Artisan)
         */
        'console_log_suffix' => env('SQL_LOGGER_CONSOLE_SUFFIX', ''),

        /*
         * Extension for log files
         */
        'extension' => env('SQL_LOGGER_LOG_EXTENSION', '.sql'),
    ],

    'formatting' => [
        /*
         * Whether new lines should be replaced by spaces (to keep query in single line)
         */
        'new_lines_to_spaces' => env('SQL_LOGGER_FORMAT_NEW_LINES_TO_SPACES', false),

        /*
         * Single entry format. Available options:
         *
         * - [origin] - where this query coming from - request or artisan
         * - [query_nr] - query number
         * - [datetime] - date and time when query was executed
         * - [query_time] - how long query was executed
         * - [query] - query itself
         * - [separator] - extra separator line to make it easier to see where next query starts
         * - \n - new line separator.
         */
        'entry_format' => env('SQL_LOGGER_FORMAT_ENTRY_FORMAT', "/* [origin]\n   Query [query_nr] - [datetime] [[query_time]] */\n[query]\n[separator]\n"),
    ],

    'all_queries' => [
        /*
         * Whether all SQL queries should be logged
         */
        'enabled' => env('SQL_LOGGER_ALL_QUERIES_ENABLED', true),

        /*
         * Whether log (for all queries, not for slow queries) should be overridden.
         * It might be useful when you test some functionality and you want to
         * compare your queries (or number of queries) - be aware that when using
         * AJAX it will override your log file in each request
         */
        'override_log' => env('SQL_LOGGER_ALL_QUERIES_OVERRIDE', false),

        /*
         * Pattern that should be matched to log query. By default all queries are logged but using
         * as pattern for example #^SELECT.*$#i will log only SELECT queries
         */
        'pattern' => env('SQL_LOGGER_ALL_QUERIES_PATTERN', '#.*#i'),

        /*
         * Log file name without extension - elements between [ and ] characters will be parsed
         * according to format used by http://php.net/manual/en/function.date.php
         */
        'file_name' => env('SQL_LOGGER_ALL_QUERIES_FILE_NAME', '[Y-m-d]-log'),
    ],

    'slow_queries' => [
        /*
         * Whether slow SQL queries should be logged (you can log all queries and
         * also slow queries in separate file or you might to want log only slow
         * queries)
         */
        'enabled' => env('SQL_LOGGER_SLOW_QUERIES_ENABLED', true),

        /*
         * Time of query (in milliseconds) when this query is considered as slow
         */
        'min_exec_time' => env('SQL_LOGGER_SLOW_QUERIES_MIN_EXEC_TIME', 100),

        /*
         * Pattern that should be matched to log slow query. By default all queries are logged but
         * using as pattern for example #^SELECT.*$#i will log only SELECT queries
         */
        'pattern' => env('SQL_LOGGER_SLOW_QUERIES_PATTERN', '#.*#i'),

        /*
         * Slow log file name without extension - elements between [ and ] characters will be parsed
         * according to format used by http://php.net/manual/en/function.date.php
         */
        'file_name' => env('SQL_LOGGER_SLOW_QUERIES_FILE_NAME', '[Y-m-d]-slow-log'),
    ],
];
