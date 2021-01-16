<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ env('APP_NAME') }}</title>

    @yield('headtags')

    <script src="/jquery/jquery-1.12.0.min.js"></script>

    <link href="/bulma-0.9.0/bulma.min.css" rel="stylesheet">

    <link href="/assets/style.css" rel="stylesheet">
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            margin: 0;
        }

        .position-ref {
            position: relative;
        }

        .full-height {
            min-height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        section {
            max-width: 600px;
        }

    </style>
</head>
<body>


    <div class="flex-center position-ref full-height">
    @yield('content')
    </div>

    <script src="/assets/script.js"></script>
    @yield('scripts')

</body>
</html>
