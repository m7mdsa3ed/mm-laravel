<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Loading...</title>
        <script>
            @if ($payload)
                window.opener.postMessage({
                    source: "oauth-handler",
                    payload: @json($payload)
                }, `{{ env('FRONTEND_URL') }}`)
            @endif

            window.close()
        </script>
    </head>

    <body>
        <h1>Hello, world!</h1>
    </body>
</html>