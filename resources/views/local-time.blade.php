@extends('layouts/main')

@section('content')
<section class="section">

@if(!$date || !$timezone)

    <div class="notification is-danger">
        Invalid input
    </div>

@else

    <input type="hidden" id="date" value="{{ $date->format('c') }}">

    <div class="widget">
        <div class="original">
            <div class="header">
                Event Time
            </div>
            <div class="time">
                {{ $date->format('g:ia') }}
            </div>
            <div class="timezone">
                {{ $timezone->getName() }}
            </div>
            <div class="date">
                {{ $date->format('l, M j, Y') }}
            </div>
        </div>

        <div class="equals">
            =
        </div>

        <div class="local">
            <div class="header">
                Your Local Time
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
                <td class="dt">{{ $tz['date']->format('D g:ia') }}</td>
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

    var date = new Date($("#date").val());

    var h = date.getHours() % 12;
    if(date.getHours() == 0)
        h = 12;
    var m = zero_pad(date.getMinutes());
    var pm = date.getHours() >= 12 ? 'pm' : 'am';
    $(".local .time").text(h+":"+m+pm);

    var tz;
    if(Intl) {
        tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } else {
        tz = tz_minutes_to_offset(date.getTimezoneOffset());
    }
    $(".local .timezone").text(tz);

    var day;
    switch(date.getDay()) {
        case 0: day = 'Sunday'; break;
        case 1: day = 'Monday'; break;
        case 2: day = 'Tuesday'; break;
        case 3: day = 'Wednesday'; break;
        case 4: day = 'Thursday'; break;
        case 5: day = 'Friday'; break;
        case 6: day = 'Saturday'; break;
    }
    var month;
    switch(date.getMonth()) {
        case 0: month = "Jan"; break;
        case 1: month = "Feb"; break;
        case 2: month = "Mar"; break;
        case 3: month = "Apr"; break;
        case 4: month = "May"; break;
        case 5: month = "Jun"; break;
        case 6: month = "Jul"; break;
        case 7: month = "Aug"; break;
        case 8: month = "Sep"; break;
        case 9: month = "Oct"; break;
        case 10: month = "Nov"; break;
        case 11: month = "Dec"; break;
    }
    $(".local .date").text(day+", "+month+" "+date.getDate()+", "+(1900+date.getYear()));

});

function zero_pad(num) {
  num = "" + num;
  if(num.length == 1) {
    num = "0" + num;
  }
  return num;
}

function tz_minutes_to_offset(minutes) {
  var hours = zero_pad(Math.floor(Math.abs(minutes / 60)));
  var min = zero_pad(Math.abs(minutes) % 60);
  return (minutes > 0 ? '-' : '+') + hours + ":" + min;
}

</script>

</section>
@endsection
