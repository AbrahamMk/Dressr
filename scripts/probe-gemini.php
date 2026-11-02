<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = (string) config('ai.keys.gemini');
$model = (string) config('ai.model');
$url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

$r = Illuminate\Support\Facades\Http::timeout(20)
    ->withHeaders([
        'Content-Type' => 'application/json',
        'x-goog-api-key' => $key,
    ])
    ->post($url, [
        'contents' => [['parts' => [['text' => 'Reply with OK']]]],
    ]);

$err = data_get($r->json(), 'error.message') ?: 'ok';
echo "model={$model} status={$r->status()} detail=".substr((string) $err, 0, 200).PHP_EOL;
