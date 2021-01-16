<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Meetable</title>

        <link href="/bulma-0.9.0/bulma.min.css" rel="stylesheet">

        <link href="/assets/style.css" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title m-b-md" style="font-weight: normal;">
                    Meetable
                </div>

                <article class="message is-danger" style="max-width: 600px;">
                    <div class="message-header">Missing Dependencies</div>
                    <div class="message-body content">
                        <p>The <code>vendor</code> folder is missing. This likely happened because you installed a development version from git and have not yet run <code>composer install</code>.</p>
                        <p>You can alternatively download a <a href="https://github.com/aaronpk/Meetable/releases">release version</a> and it will include everything you need.</p>
                    </div>
                </article>

            </div>
        </div>
    </body>
</html>
