<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public function index(): JsonResponse
    {
        $vendors = Vendor::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($vendors);
    }

    public function show(string $slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $slug)->firstOrFail();

        return response()->json($vendor);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo'        => 'nullable|string',
            'website'     => 'nullable|url',
            'is_active'   => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $vendor = Vendor::create($data);

        return response()->json($vendor, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo'        => 'nullable|string',
            'website'     => 'nullable|url',
            'is_active'   => 'boolean',
        ]);

        $vendor->update($data);

        return response()->json($vendor);
    }

    public function destroy(int $id): JsonResponse
    {
        Vendor::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
