<?php

namespace App\Imports;

use App\Product;
use App\ProductsSku;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use TCG\Voyager\Models\Category;

class ProductImportClass implements ToModel
{

    public function model(array $row)
    {
        $attributes = $row[6];

        $product = new Product();
        $product->name = $row[0];
        $product->en_name = $row[1];
        $product->ku_name = $row[2];
        $product->description = $row[3];
        $product->en_description = $row[4];
        $product->ku_description = $row[5];
        $product->files = [];
        $category = Category::where('name', $row[15])->first();
        $product->category_id = $category->id;
        $product->save();

        $sku = new ProductsSku();
        $sku->product_id = $product->id;
        $sku->price = $row[16];
        $sku->currency = "IQD";
        $sku->save();


        dd($attributes);
    }
}
