<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriesController extends Controller
{
    public function viewAny()
    {
        return Category::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
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
    }
}
