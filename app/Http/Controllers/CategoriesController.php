<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Queries\CategoryDetailsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoriesController extends Controller
{
    public function viewAny(): JsonResponse
    {
        $user = auth()->user();

        $categories = Category::query()
            ->where('categories.user_id', $user->id)
            ->withBalancies($user)
            ->withcount([
                'transactions',
            ])
            ->get();

        return response()->json($categories);
    }

    /** @throws ValidationException */
    public function save(Request $request, Category $category = null): JsonResponse
    {
        $userId = auth()->id();

        $category ??= new Category();

        $this->validate($request, [
            'name' => 'required',
        ]);

        $category->user()->associate($userId);

        $inputs = $request->only([
            'name',
            'parent_id',
        ]);

        $category->fill($inputs)
            ->save();

        $category = $category->newQuery()
            ->whereKey($category->id)
            ->withBalancies()
            ->withcount([
                'transactions',
            ])
            ->first();

        return response()
            ->json($category);
    }

    public function delete(Category $category): JsonResponse
    {
        $category->delete();

        return response()
            ->json(null, 204);
    }

    public function details(Request $request, int $categoryId): JsonResponse
    {
        $from = $request->date('from') ?? now()->subYears(2);

        $to = $request->date('to') ?? now();

        $data = CategoryDetailsQuery::get($from, $to, $categoryId);

        return response()
            ->json($data);
    }
}
