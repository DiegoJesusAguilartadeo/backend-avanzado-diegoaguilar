<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Pasarela de Pago Stripe
 *
 * Endpoints para el procesamiento de eventos asíncronos y webhooks enviados por Stripe.
 */
class StripeWebhookController extends Controller
{
    /**
     * Procesar Webhook de Stripe
     *
     * Recibe y verifica las notificaciones de eventos desde Stripe.
     * Al recibir un evento 'checkout.session.completed', actualiza la orden a 'paid',
     * el pago a 'completed' y descuenta el stock de productos en una transacción atómica.
     *
     * @unauthenticated
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        Log::info('[Webhook Stripe] Petición entrante recibida.');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            Log::info('[Webhook Stripe] Evento verificado correctamente. Tipo: ' . $event->type);
        } catch (\UnexpectedValueException $e) {
            Log::error('[Webhook Stripe] Payload inválido: ' . $e->getMessage());
            return response()->json(['error' => 'Payload inválido'], Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            Log::error('[Webhook Stripe] Falló la verificación de la firma. Revisa el STRIPE_WEBHOOK_SECRET en tu .env: ' . $e->getMessage());
            return response()->json(['error' => 'Firma no válida'], Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            Log::info("[Webhook Stripe] Procesando checkout.session.completed para Order ID: " . ($orderId ?? 'NULL'));

            if (!$orderId) {
                Log::warning('[Webhook Stripe] La sesión de Checkout no contenía order_id en metadata.');
                return response()->json(['status' => 'ignored', 'reason' => 'Sin order_id en metadata'], Response::HTTP_OK);
            }

            DB::transaction(function () use ($orderId, $session) {
                // 1. Obtener la orden con bloqueo pesimista
                $order = Order::with('items')->lockForUpdate()->find($orderId);

                if (!$order) {
                    Log::error("[Webhook Stripe] No se encontró la Orden con ID: {$orderId}");
                    return;
                }

                if ($order->status === 'paid') {
                    Log::info("[Webhook Stripe] La Orden ID: {$orderId} ya había sido marcada como 'paid'. Ignorando evento duplicado.");
                    return;
                }

                // 2. Actualizar estado de la Orden
                $order->update(['status' => 'paid']);
                Log::info("[Webhook Stripe] Orden ID: {$orderId} actualizada a 'paid'.");

                // 3. Actualizar el estado del Pago y asociar Stripe Payment Intent
                Payment::where('order_id', $order->id)->update([
                    'stripe_payment_id' => $session->payment_intent,
                    'status'            => 'completed',
                ]);
                Log::info("[Webhook Stripe] Pago de Orden ID: {$orderId} actualizado a 'completed' con Payment Intent: {$session->payment_intent}");

                // 4. Descontar stock de productos con bloqueo pesimista
                foreach ($order->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->decrement('stock', $item->quantity);
                        Log::info("[Webhook Stripe] Stock descontado para Producto ID {$product->id}: -{$item->quantity} unidades.");
                    } else {
                        Log::warning("[Webhook Stripe] No se encontró el Producto ID {$item->product_id} para descontar stock.");
                    }
                }
            });
        }

        return response()->json(['status' => 'success'], Response::HTTP_OK);
    }
}