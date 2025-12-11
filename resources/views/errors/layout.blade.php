<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>

    <style>
        html, body {
            background-color: #fff;
            color: #333;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
            "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            height: 100%;
        }

        .full-banner {
            width: 100%;
            display: block;
        }

        .content-box {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 20px;
        }

        .title {
            font-size: 32px;
            font-weight: 600;
            margin-top: 20px;
        }

        .message {
            font-size: 18px;
            color: #666;
            margin: 15px 0 25px 0;
        }

        .btn-home {
            display: inline-block;
            padding: 10px 20px;
            background-color: #c0392b;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

@yield('banner')

<div class="content-box">
    @yield('content')
</div>

</body>
</html>
