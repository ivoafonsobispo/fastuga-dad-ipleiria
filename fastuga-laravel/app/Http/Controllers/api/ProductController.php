<?php

namespace App\Http\Controllers\api;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\OrderItems;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.manager', ['except' => [
            'getProductOfOrderItems',
            'getProductsTypes',
            'index',
            'show',
            'getBestProducts',
            'getWorstProducts',
        ]]);
    }

    public function getProductOfOrderItems(OrderItems $orderItems)
    {
        return new ProductResource($orderItems->product);
    }

    public function getProductsTypes()
    {
        return Product::groupBy('type')->pluck('type');
    }

    public function index()
    {
        // Get the collection and sort it
        $collection = Product::all();
        $sortedCollection = $collection->sortBy(function ($item) {
            return Str::ascii($item->name);
        });

        // Return the sorted collection as a resource
        return ProductResource::collection($sortedCollection);
    }

    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        if($request->has('photo_url')){
            $image_64 = $request["photo_url"];
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png, ...
            //  image data to decode (eg: data:image/png;base64,imgData..)
            $replace = substr($image_64, 0, strpos($image_64, ',')+1); //
            // find substring to replace
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(16).'.'.$extension;

            Storage::put('public/products/'.$imageName, base64_decode($image));

            $validatedData["photo_url"] = $imageName;
        }

        $capitalizedName = ucfirst($validatedData["name"]);
        $validatedData["name"] = $capitalizedName;

        $newProduct = Product::create($validatedData);
        return new ProductResource($newProduct);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();

        // Check if New Photo Uploaded
        if($request->has('photo_url') && Str::length($request["photo_url"]) > 21){
            // Delete Existing Photo
            if(Storage::disk('public')->exists('products/'.$product->photo_url)) {
                Storage::disk('public')->delete('products/'.$product->photo_url);
            }

            $image_64 = $request["photo_url"];

            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];   // .jpg .png, ...
            //  image data to decode (eg: data:image/png;base64,imgData..)
            $replace = substr($image_64, 0, strpos($image_64, ',')+1); //

            // find substring to replace
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);

            $imageName = Str::random(16).'.'.$extension;

            Storage::put('public/products/'.$imageName, base64_decode($image));
            $validatedData["photo_url"] = $imageName;
        }

        $product->fill($validatedData);
        $product->save();

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
    }


    //Manager Statistics - maybe change later
    public function getBestProducts()
    {
        $this->authorize('statisticsManager', 'App\Models\User');// middleware
        $items = OrderItems::orderBy('count', 'DESC')->groupBy('product_id')
        ->selectRaw('count(product_id) as count, product_id')
        ->pluck('count','product_id')->take(5);

        foreach($items as $key =>$item){
            $final[$item]= Product::where('id', $key)->value('name');
        }
        return $final;
    }

    public function getWorstProducts()
    {
        $this->authorize('statisticsManager', 'App\Models\User');// middleware

        $items = OrderItems::orderBy('count', 'ASC')->groupBy('product_id')
        ->selectRaw('count(product_id) as count, product_id')
        ->pluck('count','product_id')->take(5);

        foreach($items as $key =>$item){
            $final[$item]= Product::where('id', $key)->value('name');
        }
        return $final;
    }
}
