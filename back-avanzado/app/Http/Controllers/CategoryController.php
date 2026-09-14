<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Gestión de Categorías
 *
 * Endpoints para la consulta pública y administración de categorías de productos.
 */
class CategoryController extends Controller
{
    /**
     * Listar Categorías
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->get();

        return response()->json([
            'data' => $categories,
        ], Response::HTTP_OK);
    }

    /**
     * Mostrar Categoría
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category->load('products'),
        ], Response::HTTP_OK);
    }

    /**
     * Crear Categoría
     *
     * @authenticated
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = DB::transaction(function () use ($request) {
            return Category::create($request->validated());
        });

        return response()->json([
            'message' => 'Categoría creada con éxito.',
            'data' => $category,
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar Categoría
     *
     * @authenticated
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updatedCategory = DB::transaction(function () use ($request, $category) {
            $category->update($request->validated());
            return $category;
        });

        return response()->json([
            'message' => 'Categoría actualizada con éxito.',
            'data' => $updatedCategory,
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar Categoría
     *
     * @authenticated
     */
    public function destroy(Category $category): JsonResponse
    {
        DB::transaction(function () use ($category) {
            $category->delete();
        });

        return response()->json([
            'message' => 'Categoría eliminada con éxito.',
        ], Response::HTTP_OK);
    }
}