<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Catálogo y Categorías (Lectura abierta)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Callbacks Públicos de Stripe Checkout
Route::prefix('checkout')->group(function () {
    Route::get('/success', [StripeCheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/cancel', [StripeCheckoutController::class, 'cancel'])->name('checkout.cancel');
});

// Webhook de Stripe (Sin middleware JWT, validado vía Stripe Signature)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
/*
|--------------------------------------------------------------------------
| 2. RUTAS CLIENTES AUTENTICADOS (JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Carrito de compras
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/{itemId}', [CartController::class, 'destroy']);
    

    // Órdenes y Checkout del cliente
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/stripe/checkout', [StripeCheckoutController::class, 'createSession']);
});

/*
|--------------------------------------------------------------------------
| 3. RUTAS SOLO ADMINISTRADOR (JWT + Middleware Role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Gestión de Categorías
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Gestión de Productos
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Gestión de Órdenes
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
});