@extends('layouts/main')

@section('content')
<section class="section event">

<div class="content">
    <h1>Pending Responses</h1>

    @if(isset($event))
    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
    @endif

    <p>Deleting a response will prevent that webmention URL from ever appearing again even if it is re-sent.</p>
</div>


<div class="responses comments">
    <ul>
        @foreach($responses as $response)
            <li id="response-{{ $response->id }}">
                @if(!isset($event))
                    <h3 class="subtitle">
                        <a href="{{ $response->event->permalink() }}">
                            {{ $response->event->name }}
                        </a>
                        <br>
                        @icon(calendar-alt)
                        {!! $response->event->date_summary() !!}
                    </h3>
                @endif

                <div class="level" style="margin-bottom: 0; align-items: start;">
                  <div class="level-left">
                    @include('components/response-author', ['response' => $response])
                  </div>
                  <div class="level-right">

                    <div class="buttons has-addons with-dropdown">

                        <a class="button view-response-details" href="{{ route('get-response-details', [$response->event, $response]) }}">
                            <span class="icon">@icon(info-circle)</span>
                            <span>View Details</span>
                        </a>
                        <a class="button is-primary approve-response" href="{{ route('approve-response', [$response->event, $response]) }}">
                            <span class="icon">@icon(check)</span>
                            <span>Approve</span>
                        </a>
                        <a class="button is-danger delete-response" href="{{ route('delete-response', [$response->event, $response]) }}">
                            <span class="icon">@icon(trash)</span>
                            <span>Delete</span>
                        </a>

                    </div>

                  </div>
                </div>

                @include('components/response-contents', ['response' => $response])
            </li>
        @endforeach
    </ul>
</div>

@include('components/response-details-modal')


<style>
.event .responses {
    padding: 0;
}
.responses li {
    border-bottom: 1px #ddd solid;
    padding-bottom: 0.75em;
}
</style>

@include('components/response-editing-js')

{{ csrf_field() }}

</section>
@endsection
