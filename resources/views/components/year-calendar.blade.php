{{-- Twelve mini calendars for a year, each day shaded by how many events start on it and linking
     to that day's page. $year is the year, $counts is [Y-m-d => number of events]. --}}
@php
use App\Helpers\Dates;

$today = date('Y-m-d');
// Weeks start on Monday. The headings are the first two letters of the site's short weekday names.
$weekdays = [];
for($d = 0; $d < 7; $d++)
    $weekdays[] = mb_substr(Dates::format(mktime(0, 0, 0, 1, 5 + $d, 2026), 'weekday_short'), 0, 2);

// The shade for a number of events: 1, 2-3, 4-6, 7 or more
$shade = function($count) {
    if($count >= 7) return 4;
    if($count >= 4) return 3;
    if($count >= 2) return 2;
    return 1;
};
@endphp
<nav class="year-calendar" aria-label="{{ __('events.year_calendar') }}">
@for($m = 1; $m <= 12; $m++)
    @php
    $first = mktime(0, 0, 0, $m, 1, $year);
    $days_in_month = (int)date('t', $first);
    // Monday = 0 ... Sunday = 6
    $offset = ((int)date('N', $first)) - 1;
    @endphp
    <div class="month">
        <a href="{{ route('month', [$year, sprintf('%02d', $m)]) }}" class="month-name">{{ Dates::format($first, 'month') }}</a>
        <table>
            <thead>
                <tr>
                    @foreach($weekdays as $weekday)
                        <th>{{ $weekday }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($cell = 0; $cell < $offset + $days_in_month; $cell += 7)
                <tr>
                    @for($i = $cell; $i < $cell + 7; $i++)
                        @php
                        $day = $i - $offset + 1;
                        $date = ($day >= 1 && $day <= $days_in_month) ? sprintf('%04d-%02d-%02d', $year, $m, $day) : null;
                        $count = $date ? ($counts[$date] ?? 0) : 0;
                        @endphp
                        @if(!$date)
                            <td></td>
                        @elseif($count)
                            <td class="has-events has-events-{{ $shade($count) }} {{ $date == $today ? 'is-today' : '' }}">
                                <a href="{{ route('day', [$year, sprintf('%02d', $m), sprintf('%02d', $day)]) }}" class="has-tooltip-top" data-tooltip="{{ trans_choice('events.event_count', $count, ['count' => $count]) }}">{{ $day }}</a>
                            </td>
                        @else
                            <td class="{{ $date == $today ? 'is-today' : '' }}">{{ $day }}</td>
                        @endif
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
@endfor
</nav>
