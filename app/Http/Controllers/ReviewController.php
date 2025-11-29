<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    public function index(Request $request)
{
    $query = Review::with('user:user_id,full_name'); 
    
    // Use the $query variable instead of Review::paginate()
    $reviews = $query->paginate($request->get('perPage', 15))
        ->appends($request->query());

    return response(['success' => true, 'data' => $reviews]);
}

public function show($id)
{
    $review = Review::with('user:user_id,full_name')->findOrFail($id);
    return response(['success' => true, 'data' => $review]);
}

    public function store(Request $request)
    {
         if ($request->user()->role_id !== 2) {
        return response(['success' => false, 'message' => 'Cannot create review'], 403);
    }
        $formFields = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string'
        ]);

        $formFields['user_id'] = $request->user()->user_id;

        $review = Review::create($formFields);

        return response([
            'success' => true,
            'data' => $review
        ], 201);
    }

 public function update($id, Request $request)
{
    $user = $request->user();
    if ($user->role_id !== 2) {
        return response(['success' => false, 'message' => 'Cannot update review'], 403);
    }

    $review = Review::findOrFail($id);
    if ($review->user_id !== $user->user_id) {
        return response(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $formFields = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'review_text' => 'nullable|string'
    ]);

    $review->update($formFields);

    return response([
        'success' => true,
        'data' => $review
    ], 201);
}

    public function destroy($id, Request $request)
    {
        $review = Review::findOrFail($id);
        $user = $request->user(); 

        if ($user->role !== 'admin' && $review->user_id !== $user->user_id) {
            return response(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response(['success' => true], Response::HTTP_NO_CONTENT);
    }
}