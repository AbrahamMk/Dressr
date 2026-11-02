<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong — Dressr</title>
    <style>
        body { font-family: "Instrument Sans", system-ui, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; }
        main { max-width: 28rem; text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p { color: #64748b; margin: 0 0 1.25rem; line-height: 1.5; }
        a { color: #2563eb; font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main>
        <h1>Something went wrong</h1>
        <p>Dressr hit an unexpected error. Try again in a moment. If it keeps happening, check the server logs.</p>
        <p><a href="{{ url('/dashboard') }}">Go to dashboard</a></p>
    </main>
</body>
</html>
