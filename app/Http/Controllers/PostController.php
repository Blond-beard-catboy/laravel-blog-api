<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['user', 'tags']);

        // Фильтрация по тегам (query param: ?tags[]=1&tags[]=2)
        if ($request->has('tags')) {
            $tagIds = $request->input('tags');
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        $posts = $query->paginate(15);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return new PostResource($post->load(['user', 'tags']));
    }

    public function show(Post $post)
    {
        return new PostResource($post->load(['user', 'tags', 'comments' => function ($q) {
            $q->whereNull('parent_id')->with('children.user');
        }]));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->only(['title', 'content']));

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return new PostResource($post->load(['user', 'tags']));
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(null, 204);
    }
}