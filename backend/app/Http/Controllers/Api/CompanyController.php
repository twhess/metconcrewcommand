<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['locations', 'primaryLocation', 'contacts', 'createdBy', 'updatedBy']);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $companies = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,vendor,contractor,internal',
            'main_phone' => 'nullable|string|max:20',
            'main_email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $company = Company::create($validated);
        $company->load(['locations', 'primaryLocation', 'contacts']);

        return response()->json([
            'success' => true,
            'message' => 'Company created successfully',
            'data' => $company,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $company = Company::with(['locations', 'primaryLocation', 'contacts.roles', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:customer,vendor,contractor,internal',
            'main_phone' => 'nullable|string|max:20',
            'main_email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $company->update($validated);
        $company->load(['locations', 'primaryLocation', 'contacts']);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data' => $company,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company deleted successfully',
        ]);
    }
}
