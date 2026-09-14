<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[OA\Schema(
    schema: "Product",
    required: ["name", "price", "category_id"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Iphone 18 Ultra"),
        new OA\Property(property: "price", type: "numeric", example: 119.95),
        new OA\Property(property: "stock", type: "integer", example: 15),
        new OA\Property(property: "is_featured", type: "boolean", example: true),
        new OA\Property(property: "category_id", type: "integer", example: 3),
    ]
)]

class Product extends Model
{
    protected $fillable = ['name', 'price', 'stock', 'is_featured', 'category_id'];


    public function category(){
        return $this->belongsTo(Category::class);
    }

    // $query = 'select * from products where category_id = 1
    public function scopeInStock($query){
        return $query->where('stock', '>', 0);
    }
    // $query = 'select * from products where category_id = 1 and stock > 0
    // para AND: anidar wheres...
    // para OR: usar orwhere


    public function orderItems(): HasMany
{
    return $this->hasMany(OrderItem::class);
}


    // 1499.9995
    protected function formattedPrice() : Attribute {
        return Attribute :: make(
            get: fn () => '$' . number_format($this->price,2),
        );
    }
    // $1,499.99
    /* 
        [
            "price" : "$1,599.99",
        ]
    */
    
    public static function productsByCategory()
    {
        return DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('categories.name as categoria', DB::raw('count(*) as total_productos'))
            ->groupBy('categories.name')
            ->orderByDesc('total_productos')
            ->get();
    }
}
