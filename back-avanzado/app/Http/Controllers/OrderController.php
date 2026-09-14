<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Gestión de Órdenes
 */
class OrderController extends Controller
{
    /**
     * Listar órdenes del usuario autenticado
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.product', 'payment'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($orders, Response::HTTP_OK);
    }

    /**
     * Crear Orden desde el Carrito y Generar Sesión en Stripe
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Buscar la cabecera del carrito
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'message' => 'El carrito de compras está vacío.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Obtener los ítems asociados al carrito
        $cartItems = CartItem::with('product')->where('cart_id', $cart->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'El carrito de compras está vacío.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = DB::transaction(function () use ($user, $cart, $cartItems) {
                $totalAmount = 0;
                $itemsToCreate = [];

                // 3. Validar stock y descontar inventario con bloqueo pesimista
                foreach ($cartItems as $cartItem) {
                    $product = Product::lockForUpdate()->find($cartItem->product_id);

                    if (!$product) {
                        throw new \Exception("El producto ID {$cartItem->product_id} no existe.");
                    }

                    if ($product->stock < $cartItem->quantity) {
                        throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                    }

                    $subtotal = $product->price * $cartItem->quantity;
                    $totalAmount += $subtotal;

                    // Descontar stock directamente
                    $product->decrement('stock', $cartItem->quantity);

                    $itemsToCreate[] = [
                        'product_id' => $product->id,
                        'quantity'   => $cartItem->quantity,
                        'price'      => $product->price,
                    ];
                }

                // 4. Crear la Orden
                $order = Order::create([
                    'user_id' => $user->id,
                    'total'   => $totalAmount,
                    'status'  => 'pending',
                ]);

                // 5. Insertar los ítems de la orden
                foreach ($itemsToCreate as $item) {
                    $order->items()->create($item);
                }

                // 6. Generar Checkout Session en Stripe
                $stripe = new StripeClient(config('services.stripe.secret'));
                $lineItems = [];

                foreach ($order->items()->with('product')->get() as $item) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency'     => 'usd',
                            'product_data' => ['name' => $item->product->name],
                            'unit_amount'  => (int) round($item->price * 100),
                        ],
                        'quantity'   => $item->quantity,
                    ];
                }

                $baseUrl = config('app.url', 'http://127.0.0.1:8000');

                $session = $stripe->checkout->sessions->create([
                    'payment_method_types' => ['card'],
                    'line_items'           => $lineItems,
                    'mode'                 => 'payment',
                    'success_url'          => $baseUrl . '/api/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'           => $baseUrl . '/api/checkout/cancel',
                    'metadata'             => [
                        'order_id' => (string) $order->id,
                        'user_id'  => (string) $user->id,
                    ],
                ]);

                // 7. Registrar transacción en Payments
                Payment::create([
                    'order_id'          => $order->id,
                    'stripe_session_id' => $session->id,
                    'amount'            => $totalAmount,
                    'status'            => 'pending',
                ]);

                // 8. Limpiar carrito
                CartItem::where('cart_id', $cart->id)->delete();
                $cart->delete();

                return [
                    'order'        => $order->load('items.product', 'payment'),
                    'checkout_url' => $session->url,
                ];
            });

            return response()->json([
                'message'      => 'Orden creada exitosamente.',
                'order'        => $result['order'],
                'checkout_url' => $result['checkout_url'],
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la orden.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Ver Detalle de Orden
     * @authenticated
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ((int) $order->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'No tienes permiso sobre esta orden.'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json($order->load(['items.product', 'payment']), Response::HTTP_OK);
    }

    /**
     * Eliminar Orden (Solo Administrador)
     * @authenticated
     */
    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json([
            'message' => 'Orden eliminada correctamente.'
        ], Response::HTTP_OK);
    }
}