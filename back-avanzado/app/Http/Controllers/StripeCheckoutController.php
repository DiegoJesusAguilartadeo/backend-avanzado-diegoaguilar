<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Pasarela de Pago Stripe
 */
class StripeCheckoutController extends Controller
{
    /**
     * Re-generar Sesión de Checkout para la última orden pendiente del usuario
     * @authenticated
     */
    public function createSession(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Buscar automáticamente la última orden 'pending' del usuario logueado
        // Si el cliente envía opcionalmente un 'order_id' lo usa, sino toma la última pendiente.
        $orderQuery = Order::with('items.product')
            ->where('user_id', $user->id)
            ->where('status', 'pending');

        if ($request->has('order_id')) {
            $orderQuery->where('id', $request->input('order_id'));
        }

        $order = $orderQuery->latest()->first();

        if (!$order) {
            return response()->json([
                'message' => 'No tienes ninguna orden pendiente de pago.'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $lineItems = [];

            foreach ($order->items as $item) {
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

            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'stripe_session_id' => $session->id,
                    'amount'            => $order->total,
                    'status'            => 'pending',
                ]
            );

            return response()->json([
                'message'      => 'Sesión de pago obtenida exitosamente.',
                'order_id'     => $order->id,
                'checkout_url' => $session->url
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar la sesión de checkout.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function success(Request $request): JsonResponse
    {
        return response()->json([
            'message'    => 'Pago procesado o en verificación.',
            'session_id' => $request->query('session_id')
        ], Response::HTTP_OK);
    }

    public function cancel(): JsonResponse
    {
        return response()->json([
            'message' => 'El proceso de pago fue cancelado por el usuario.'
        ], Response::HTTP_OK);
    }
}