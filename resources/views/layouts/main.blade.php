@php
use App\Setting;
use App\Response;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($page_title) ? $page_title.' | '.env('APP_NAME') : env('APP_NAME') }}</title>

    @yield('headtags')

    <script>
    // Text used by the site's JavaScript, from resources/lang/{locale}/js.php
    window.Meetable = {{ \Illuminate\Support\Js::from(['lang' => __('js')]) }};
    </script>
    <script src="/jquery/jquery-1.12.0.min.js"></script>

    <link href="/bulma-1.0.4/bulma.css" rel="stylesheet">
    <link href="/assets/bulma-tooltip-1.2.0.min.css" rel="stylesheet">

    <link href="/assets/style.css" rel="stylesheet">

    <link rel="manifest" href="/manifest.json">

@if($favicon=Setting::value('favicon_url'))
    <link rel="shortcut icon" href="{{ $favicon }}">
@endif

@if(Setting::value('custom_global_css'))
    <link href="/custom-css" rel="stylesheet">
@endif

@if($analytics=Setting::value('analytics'))
    {!! $analytics !!}
@endif

    <script src="/assets/passkeys.js"></script>
</head>
<body>

    <main>

    <nav class="navbar" role="navigation" aria-label="{{ __('common.nav.main_navigation') }}">
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

            <a role="button" class="navbar-burger burger" aria-label="{{ __('common.nav.menu') }}" aria-expanded="false" data-target="navbarBasicExample">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarBasicExample" class="navbar-menu">
            <div class="navbar-start">
                @if(Setting::value('logo_url'))
                    <a class="navbar-item" href="{{ route('index') }}">{{ __('common.nav.upcoming_events') }}</a>
                @endif
                <a class="navbar-item" href="{{ route('archive') }}">{{ __('common.nav.past_events') }}</a>
                <a class="navbar-item" href="{{ route('tags') }}">{{ __('common.nav.discover') }}</a>
                @can('create-event')
                    @if(Setting::value('enable_unlisted_events'))
                        <a class="navbar-item" href="{{ route('unlisted') }}">{{ __('common.nav.unlisted_events') }}</a>
                    @endif
                    @if(Setting::value('enable_webmention_responses'))
                        <a class="navbar-item" href="{{ route('moderate-all-responses') }}">
                            {{ __('common.nav.moderate_responses') }}
                            {!! ($num=Response::where('approved', 0)->count()) ? "(<span class='pending-response-count'>$num</span>)" : "" !!}
                        </a>
                    @endif
                    <div class="navbar-item has-dropdown is-hoverable">
                        <a class="navbar-link" href="{{ route('new-event') }}">{{ __('common.nav.add_event') }}</a>
                        <div class="navbar-dropdown">
                            <a class="navbar-item" href="{{ route('new-event') }}">{{ __('common.nav.create_new_event') }}</a>
                            <a class="navbar-item" href="{{ route('import-event') }}">{{ __('common.nav.import_from_url') }}</a>
                        </div>
                    </div>
                @endcan
            </div>
            <div class="navbar-end">
                @can('create-event')
                    @if(true || Setting::value('enable_recurring_events'))
                        <a class="navbar-item" href="{{ route('templates') }}">{{ __('common.nav.recurring_events') }}</a>
                    @endif
                @endcan
                @can('manage-site')
                    <a class="navbar-item" href="{{ route('settings') }}">{{ __('common.nav.settings') }}</a>
                @endcan
                @if(Auth::user() && env('AUTH_METHOD') == 'discord')
                    <a class="navbar-item" href="{{ route('discord-notifications') }}">{{ __('common.nav.discord') }}</a>
                @endif
                @if(Auth::user())
                    <a class="navbar-item" href="{{ route('profile') }}">{{ __('common.nav.profile') }}</a>
                @endif
                @if(Auth::user() && !Setting::value('auth_hide_logout'))
                    <a class="navbar-item" href="{{ route('logout') }}">{{ __('common.nav.log_out') }}</a>
                @elseif(!Auth::user() && !Setting::value('auth_hide_login'))
                    <a class="navbar-item" href="{{ route('login') }}">{{ __('common.nav.log_in') }}</a>
                @endif
            </div>
        </div>
    </nav>

    <div>
        <!-- customize with css in the settings page -->
        <div id="site-banner"><div class="left"></div><div class="right"></div></div>

        @yield('content')
    </div>

    <footer class="site-footer">
        <div>
            {!! __('common.footer', ['meetable' => '<a href="https://github.com/aaronpk/Meetable">meetable</a>']) !!}
        </div>
    </footer>

    </main>

    <script src="/assets/script.js?v=20260916"></script>
    @yield('scripts')

</body>
</html>
