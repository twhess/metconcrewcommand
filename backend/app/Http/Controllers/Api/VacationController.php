<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VacationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vacation::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('approved')) {
            $query->where('approved', $request->boolean('approved'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $vacations = $query->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $vacations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:vacation,sick,personal,other',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['approved'] = false;

        $vacation = Vacation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vacation request created successfully',
            'data' => $vacation->load('user'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $vacation = Vacation::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vacation,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vacation = Vacation::findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'type' => 'nullable|in:vacation,sick,personal,other',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $vacation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vacation updated successfully',
            'data' => $vacation->load('user'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $vacation = Vacation::findOrFail($id);
        $vacation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vacation deleted successfully',
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $vacation = Vacation::findOrFail($id);

        $vacation->update([
            'approved' => true,
            'approved_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation approved successfully',
            'data' => $vacation->load('user'),
        ]);
    }
}
