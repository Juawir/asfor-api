<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('role', 'admin')->first();
$request = \Illuminate\Http\Request::create('/api/elections', 'POST', [
    'title' => 'Test',
    'candidates' => [
        ['userId' => (string) $user->id],
        ['userId' => (string) $user->id]
    ]
]);
$request->setUserResolver(function() use ($user) { return $user; });

$controller = app(\App\Http\Controllers\Api\ElectionController::class);
try {
    $response = $controller->store($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
