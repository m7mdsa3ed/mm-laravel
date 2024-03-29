<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    public function viewAny()
    {
        return Tag::where('user_id', auth()->id())
            ->withCount('transactions')
            ->get();
    }

    public function save(Request $request, Tag $tag = null)
    {
        $tag ??= new Tag();

        $this->validate($request, [
            'name' => 'required',
        ]);

        $fields = (object) $request->only([
            'name',
        ]);

        $fields->slug = str($fields->name)->slug();

        $tag->user()->associate(auth()->id());

        $tag->fill((array) $fields)
            ->save();

        return $tag;
    }

    public function delete(Tag $tag)
    {
        $tag->delete();

        return response()->noContent();
    }
}
