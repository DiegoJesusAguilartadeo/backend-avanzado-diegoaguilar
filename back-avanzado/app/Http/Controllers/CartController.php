<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Obtener el carrito del usuario.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::with('items.product')->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json($cart);
    }
/**
 * Eliminar ítem del carrito
 *
 * Remueve un producto específico del carrito de compras del usuario autenticado.
 *
 * @authenticated
 */
public function destroy(int $itemId): JsonResponse
{
    $cartItem = CartItem::whereHas('cart', function ($query) {
        $query->where('user_id', auth()->id());
    })->findOrFail($itemId);

    $cartItem->delete();

    return response()->json([
        'message' => 'Producto eliminado del carrito exitosamente.'
    ], Response::HTTP_OK);
}



    /**
     * Agregar producto al carrito.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json(['error' => 'Stock insuficiente.'], 422);
        }

        $cart = DB::transaction(function () use ($request, $validated) {
            $cart = Cart::firstOrCreate([
                'user_id' => $request->user()->id,
            ]);

            $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $validated['quantity']);
            } else {
                $cart->items()->create([
                    'product_id' => $validated['product_id'],
                    'quantity'   => $validated['quantity'],
                ]);
            }

            return $cart->load('items.product');
        });

        return response()->json([
            'message' => 'Producto agregado al carrito exitosamente.',
            'cart'    => $cart,
        ], 200);
    }
}