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

    <script
      src="https://code.jquery.com/jquery-3.4.1.min.js"
      integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
      crossorigin="anonymous"></script>
    <script src="/semantic-ui-2.4/semantic.min.js"></script>

    <link rel="stylesheet" type="text/css" href="/semantic-ui-2.4/semantic.min.css">
    <link href="/assets/style.css" rel="stylesheet">
</head>
<body>

    @yield('content')

    <script src="/assets/script.js"></script>
    @yield('scripts')

</body>
</html>
