<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

echo "Testing Changelog API with Auth:\n";
$req = Illuminate\Http\Request::create('/api/v1/changelog', 'GET');
$req->setUserResolver(fn() => $user);
$res = $app->handle($req);
echo "Status: " . $res->getStatusCode() . "\n";
$data = json_decode($res->getContent(), true);
echo "Total items: " . count($data['data'] ?? []) . "\n";
echo "Stats: " . json_encode($data['stats'] ?? []) . "\n";

echo "\nTesting Bug Reports API with Auth:\n";
$req2 = Illuminate\Http\Request::create('/api/v1/bug-reports', 'GET');
$req2->setUserResolver(fn() => $user);
$res2 = $app->handle($req2);
echo "Status: " . $res2->getStatusCode() . "\n";
$data2 = json_decode($res2->getContent(), true);
echo "Total bugs: " . count($data2['data'] ?? []) . "\n";
echo "Stats: " . json_encode($data2['stats'] ?? []) . "\n";
