<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BookResource::collection(Book::paginate(2));
    }

    public function store(Request $request): BookResource
    {
        $validated = $request->validate([
            'title'   => 'required|string|min:3|max:255',
            'author'  => 'required|string|min:3|max:100',
            'summary' => 'required|string|min:10|max:500',
            'isbn'    => 'required|string|size:13|unique:books,isbn',
        ]);

        $book = Book::create($validated);

        return new BookResource($book);
    }

    public function show(Book $book): BookResource
    {
        $cached = Cache::remember("book-{$book->id}", 3600, fn () => $book);
        return new BookResource($cached);
    }

    public function update(Request $request, Book $book): BookResource
    {
        $validated = $request->validate([
            'title'   => 'sometimes|required|string|min:3|max:255',
            'author'  => 'sometimes|required|string|min:3|max:100',
            'summary' => 'sometimes|required|string|min:10|max:500',
            'isbn'    => 'sometimes|required|string|size:13|unique:books,isbn,' . $book->id,
        ]);

        $book->update($validated);
        Cache::forget("book-{$book->id}");

        return new BookResource($book);
    }

    public function destroy(Book $book): Response
    {
        $book->delete();
        Cache::forget("book-{$book->id}");

        return response()->noContent();
    }
}
