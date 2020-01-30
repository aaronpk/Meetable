@php
use App\Setting;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ env('APP_NAME') }}</title>

    @yield('headtags')

    <script src="/jquery/jquery-1.12.0.min.js"></script>

    <link href="/bulma-0.8.0/bulma.min.css" rel="stylesheet">

    <link href="/assets/style.css" rel="stylesheet">

    @if($favicon=Setting::value('favicon_url'))
        <link rel="shortcut icon" href="{{ $favicon }}">
    @endif

    @if($ga_id=Setting::value('ga_id'))
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga_id }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $ga_id }}');
    </script>
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
                    <a class="navbar-item" href="{{ route('new-event') }}">Add an Event</a>
                @endcan
            </div>
            @if(Setting::value('auth_show_login') || Setting::value('auth_show_logout') || Gate::allows('manage-site'))
            <div class="navbar-end">
                @can('manage-site')
                    <a class="navbar-item" href="{{ route('settings') }}">Settings</a>
                @endcan
                @if(Auth::user() && Setting::value('auth_show_logout'))
                    <a class="navbar-item" href="{{ route('logout') }}">Log Out</a>
                @elseif(!Auth::user() && Setting::value('auth_show_login'))
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
