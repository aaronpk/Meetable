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
</head>
<body>

    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <span class="navbar-item">
                <a href="{{ route('index') }}">
                @if(env('LOGO_URL'))
                    <img src="{{ env('LOGO_URL') }}" width="120">
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
                @if(env('LOGO_URL'))
                    <a class="navbar-item" href="{{ route('index') }}">Upcoming Events</a>
                @endif
                <a class="navbar-item" href="{{ route('archive') }}">Past Events</a>
                @if(Auth::user())
                    <a class="navbar-item" href="{{ route('new-event') }}">Add an Event</a>
                @endif
            </div>
        </div>
    </nav>

    @yield('content')

    <script src="/assets/script.js"></script>
    @yield('scripts')

</body>
</html>
