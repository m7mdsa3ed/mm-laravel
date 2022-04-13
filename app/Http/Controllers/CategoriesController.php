<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriesController extends Controller
{
    public function viewAny()
    {
        return Category::where('user_id', auth()->id())->selectBalance(auth()->user())
            ->withcount(['transactions' => fn ($query) => $query->withoutGlobalScope('public')])
            ->get();
    }

    public function save(Request $request, Category $category = null)
    {
        $category = $category ?? new Category;

        $this->validate($request, [
            "name"  => 'required|unique:categories,name,' . $category->id
        ]);

        $category->user()->associate(Auth::id());

        $category->fill($request->all())
            ->save();

        return $category;
    }

    public function delete(Category $category)
    {
        $category->delete();

        return ['Success'];
    }
}
