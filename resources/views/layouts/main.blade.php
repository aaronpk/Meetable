@php
use App\Setting;
use App\Response;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($page_title) ? $page_title.' | '.env('APP_NAME') : env('APP_NAME') }}</title>

    @yield('headtags')

    <script src="/jquery/jquery-1.12.0.min.js"></script>

    <link href="/bulma-0.9.0/bulma.min.css" rel="stylesheet">
    <link href="/assets/bulma-tooltip-1.2.0.min.css" rel="stylesheet">

    <link href="/assets/style.css" rel="stylesheet">

@if($favicon=Setting::value('favicon_url'))
    <link rel="shortcut icon" href="{{ $favicon }}">
@endif

@if($analytics=Setting::value('analytics'))
    {!! $analytics !!}
@endif

</head>
<body>

    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <span class="navbar-item">
                <a href="{{ route('index') }}" class="navbar-logo">
                @if($logo_url=Setting::value('logo_url'))
                    <img src="{{ $logo_url }}" style="{{ ($w=Setting::value('logo_width')) ? 'width: '.$w : '' }};
                        {{ ($h=Setting::value('logo_height')) ? 'height: '.$h.'; max-height: '.$h : '' }}">
                @else
                    {{ env('APP_NAME') }}
                @endif
                </a>
            </span>

            <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarBasicExample" class="navbar-menu">
            <div class="navbar-start">
                @if(Setting::value('logo_url'))
                    <a class="navbar-item" href="{{ route('index') }}">Upcoming Events</a>
                @endif
                <a class="navbar-item" href="{{ route('archive') }}">Past Events</a>
                <a class="navbar-item" href="{{ route('tags') }}">Discover</a>
                @can('create-event')
                    @if(Setting::value('enable_unlisted_events'))
                        <a class="navbar-item" href="{{ route('unlisted') }}">Unlisted Events</a>
                    @endif
                    @if(Setting::value('enable_webmention_responses'))
                        <a class="navbar-item" href="{{ route('moderate-all-responses') }}">
                            Moderate Responses
                            {!! ($num=Response::where('approved', 0)->count()) ? "(<span class='pending-response-count'>$num</span>)" : "" !!}
                        </a>
                    @endif
                    <div class="navbar-item has-dropdown is-hoverable">
                        <a class="navbar-link" href="{{ route('new-event') }}">Add an Event</a>
                        <div class="navbar-dropdown">
                            <a class="navbar-item" href="{{ route('import-event') }}">Import from URL</a>
                        </div>
                    </div>
                @endcan
            </div>
            @if(!Setting::value('auth_hide_login') || !Setting::value('auth_hide_logout') || Gate::allows('manage-site'))
            <div class="navbar-end">
                @can('manage-site')
                    <a class="navbar-item" href="{{ route('settings') }}">Settings</a>
                @endcan
                @if(Auth::user() && !Setting::value('auth_hide_logout'))
                    <a class="navbar-item" href="{{ route('logout') }}">Log Out</a>
                @elseif(!Auth::user() && !Setting::value('auth_hide_login'))
                    <a class="navbar-item" href="{{ route('login') }}">Log In</a>
                @endif
            </div>
            @endif
        </div>
    </nav>

    @yield('content')

    <script src="/assets/script.js"></script>
    @yield('scripts')

</body>
</html>
