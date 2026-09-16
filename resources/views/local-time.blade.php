@extends('layouts/main')

@section('content')
<section class="section">

@if(!$date || !$timezone)

    <div class="notification is-danger">
        {{ __('events.local_time.invalid_input') }}
    </div>

@else

    <input type="hidden" id="date" value="{{ $date->format('c') }}">

    <div class="widget">
        <div class="original">
            <div class="header">
                {{ __('events.local_time.event_time') }}
            </div>
            <div class="time">
                {{ \App\Helpers\Dates::format($date, 'time') }}
            </div>
            <div class="timezone">
                {{ $timezone->getName() }}
            </div>
            <div class="date">
                {{ \App\Helpers\Dates::format($date, 'weekday_date') }}
            </div>
        </div>

        <div class="equals">
            =
        </div>

        <div class="local">
            <div class="header">
                {{ __('events.local_time.your_local_time') }}
            </div>
            <div class="time"></div>
            <div class="timezone"></div>
            <div class="date"></div>
        </div>
    </div>

    <div class="contain">
        <table class="table">
        @foreach($timezones as $tz)
            <tr>
                <td class="tz">{{ $tz['name'] }}</td>
                <td class="dt">{{ \App\Helpers\Dates::format($tz['date'], 'weekday_time') }}</td>
            </tr>
        @endforeach
        </table>
    </div>

@endif

<style>
.widget {
    max-width: 720px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
}
@media(max-width: 400px) {
    .widget {
        display: block;
    }
    .equals {
        text-align: center;
    }
}
.equals {
    font-size: 40px;
}
.original, .local {
    padding: 20px;
    text-align: center;
}
.header {
    font-size: 1.1em;
    font-weight: bold;
}
.time {
    font-size: 3em;
}
.contain {
    max-width: 720px;
}
.table {
    margin-top: 2em;
    width: 100%;
}
.table .tz {
    text-align: right;
}
.table .tz, .table .dt {
    width: 50%;
}
</style>

<script>
$(function(){

    // Parsing a date string that includes a timezone offset will cause
    // the browser to return a date object in the local timezone
    var date = new Date($("#date").val());

    $(".local .time").text(date_to_display_time(date));

    var tz;
    if(Intl) {
        tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } else {
        tz = tz_minutes_to_offset(date.getTimezoneOffset());
    }
    $(".local .timezone").text(tz);

    $(".local .date").text(date.toLocaleDateString(page_locale(), {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    }));

});

</script>

</section>
@endsection
