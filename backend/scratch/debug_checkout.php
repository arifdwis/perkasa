<?php

// Boot Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CheckoutController;

// 1. Find buyer1@perkasa.test
$user = User::where('email', 'buyer1@perkasa.test')->first();
if (!$user) {
    echo "User buyer1@perkasa.test not found!\n";
    exit(1);
}
$user->load('profile');
echo "User name: " . $user->name . "\n";
echo "Profile WhatsApp: " . ($user->profile->whatsapp ?? 'null') . "\n";
echo "Profile Domisili: " . ($user->profile->domisili ?? 'null') . "\n";

// 2. Ensure they have items in cart
$cart = $user->cart()->firstOrCreate();
if ($cart->items()->count() === 0) {
    echo "Cart is empty, adding a product...\n";
    // Find an active product
    $product = \App\Models\Product::where('status', 'active')->first();
    if (!$product) {
        echo "No active products found to add to cart!\n";
        exit(1);
    }
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1
    ]);
}

echo "Cart items count: " . $cart->items()->count() . "\n";

// 3. Test checkout endpoint validation
$controller = new CheckoutController();

// Prepare request with validation payload
$requestData = [
    'nama_penerima' => 'Budi Hermawan',
    'telepon_penerima' => '0812345678',
    'alamat_penerima' => 'Jalan Mulawarman No 12',
    // 'wilayah_antar' => 'Samarinda Ulu'
];

$request = Request::create('/api/checkout', 'POST', $requestData);
$request->setUserResolver(fn() => $user);

try {
    echo "Attempting checkout with standard payload...\n";
    $response = $controller->checkout($request);
    echo "Status code: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation failed! Errors:\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "Checkout threw exception: " . $e->getMessage() . "\n";
}
