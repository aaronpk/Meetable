@extends('layouts/main')

@section('content')
<section class="section event">

<div class="content">
    <h1>Responses on {{ $event->name }}</h1>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
</div>


<div class="responses comments">
    <ul>
        @foreach($responses as $response)
            <li id="response-{{ $response->id }}">
                <div class="level" style="margin-bottom: 0; align-items: start;">
                  <div class="level-left">

                    @include('components/response-author', ['response' => $response])

                  </div>
                  <div class="level-right">

                    <div class="buttons has-addons with-dropdown">

                        <div class="dropdown is-right">
                            <div class="dropdown-trigger">
                                <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
                                    <span>Actions</span>
                                    <span class="icon is-small">@icon(angle-down)</span>
                                </button>
                            </div>

                            <div class="dropdown-menu" id="dropdown-menu" role="menu">
                                <div class="dropdown-content">
                                    <a class="dropdown-item view-response-details" href="{{ route('get-response-details', [$event, $response]) }}">
                                        <span class="icon">@icon(info-circle)</span>
                                        <span>View Details</span>
                                    </a>
                                    <a class="dropdown-item delete-response" href="{{ route('delete-response', [$event, $response]) }}">
                                        <span class="icon">@icon(trash)</span>
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>

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
