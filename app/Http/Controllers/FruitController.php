<?php

namespace App\Http\Controllers;

use App\Models\Fruit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\ProcessNewProductNotifications;

class FruitController extends Controller
{
    public function index(Request $request)
    {
        $query = Fruit::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('sortBy')) {
            switch ($request->sortBy) {
                case 'price-low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price-high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'stock':
                    $query->orderBy('stock', 'desc');
                    break;
                case 'name':
                default:
                    $query->orderBy('name', 'asc');
                    break;
            }
        }

        // Return paginated results (8 per page)
        return response()->json($query->paginate(8));
    }

    public function categories()
    {
        $categories = Fruit::select('category')->distinct()->pluck('category');
        return response()->json($categories);
    }

    public function show(Fruit $fruit)
    {
        return response()->json($fruit);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();

        // Handle base64 image upload
        if ($request->has('image_url') && $request->image_url) {
            $imageData = $request->image_url;

            // Check if it's a base64 image
            if (strpos($imageData, 'data:image/') === 0) {
                // Extract the base64 part
                $image = str_replace('data:image/', '', $imageData);
                $image = explode(';base64,', $image);
                $imageType = $image[0]; // png, jpg, etc.
                $imageBase64 = base64_decode($image[1]);

                // Generate unique filename
                $imageName = time() . '_' . Str::random(10) . '.' . $imageType;

                // Save to public/images directory
                $imagePath = public_path('images/');
                if (!file_exists($imagePath)) {
                    mkdir($imagePath, 0755, true);
                }

                file_put_contents($imagePath . $imageName, $imageBase64);
                $data['image_url'] = url('images/' . $imageName);
            }
        }

        $fruit = Fruit::create($data);

        // Dispatch notification job to queue (non-blocking) - THIS IS THE KEY CHANGE
        ProcessNewProductNotifications::dispatch($fruit)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(5)); // Small delay to ensure fruit is fully created

        return response()->json([
            'message' => 'Insert successful',
            'data' => $fruit
        ], 201);
    }

    public function update(Request $request, Fruit $fruit)
    {
        $request->validate([
            'name' => 'string',
            'price' => 'numeric|min:0',
            'category' => 'string',
            'stock' => 'integer|min:0',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();

        // Handle base64 image upload for updates
        if ($request->has('image_url') && $request->image_url) {
            $imageData = $request->image_url;

            // Check if it's a base64 image (new upload)
            if (strpos($imageData, 'data:image/') === 0) {
                // Delete old image if exists
                if ($fruit->image_url && strpos($fruit->image_url, url('')) === 0) {
                    $oldImagePath = str_replace(url(''), public_path(''), $fruit->image_url);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Extract the base64 part
                $image = str_replace('data:image/', '', $imageData);
                $image = explode(';base64,', $image);
                $imageType = $image[0];
                $imageBase64 = base64_decode($image[1]);

                // Generate unique filename
                $imageName = time() . '_' . Str::random(10) . '.' . $imageType;

                // Save to public/images directory
                $imagePath = public_path('images/');
                if (!file_exists($imagePath)) {
                    mkdir($imagePath, 0755, true);
                }

                file_put_contents($imagePath . $imageName, $imageBase64);
                $data['image_url'] = url('images/' . $imageName);
            }
        }

        $fruit->update($data);
        return response()->json($fruit);
    }

    public function destroy(Fruit $fruit)
    {
        // Prevent deletion if fruit exists in order_items
        $hasOrderItems = \DB::table('order_items')->where('fruit_id', $fruit->id)->exists();
        if ($hasOrderItems) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete fruit: it exists in order items.'
            ], 400);
        }

        // Delete associated image file
        if ($fruit->image_url && strpos($fruit->image_url, url('')) === 0) {
            $imagePath = str_replace(url(''), public_path(''), $fruit->image_url);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        if ($fruit->delete()) {
            return response()->json([
                'status' => true,
                'message' => 'Fruit deleted successfully.'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete fruit.'
            ], 500);
        }
    }
}
