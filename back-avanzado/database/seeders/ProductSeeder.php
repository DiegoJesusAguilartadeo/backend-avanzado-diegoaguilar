<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::first();

        $products = [
            [
                'name'        => 'Teclado Mecánico RGB',
                'price'       => 79.99,
                'stock'       => 25,
                'is_featured' => true,
                'category_id' => $category->id,
            ],
            [
                'name'        => 'Mouse Gamer Inalámbrico',
                'price'       => 49.99,
                'stock'       => 40,
                'is_featured' => false,
                'category_id' => $category->id,
            ],
            [
                'name'        => 'Monitor 27" IPS 144Hz',
                'price'       => 289.00,
                'stock'       => 10,
                'is_featured' => true,
                'category_id' => $category->id,
            ],
            [
                'name'        => 'Auriculares Wireless Pro',
                'price'       => 119.50,
                'stock'       => 15,
                'is_featured' => false,
                'category_id' => $category->id,
            ],
            [
                'name'        => 'Silla Ergonómica Gamer',
                'price'       => 219.99,
                'stock'       => 8,
                'is_featured' => true,
                'category_id' => $category->id,
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }
    }
}
