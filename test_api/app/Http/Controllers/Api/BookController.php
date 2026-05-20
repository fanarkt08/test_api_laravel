<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: '/books',
        summary: 'Liste paginée des livres (2 par page)',
        tags: ['Livres'],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des livres',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            [
                                'title'   => '1984',
                                'author'  => 'GEORGE ORWELL',
                                'summary' => 'Roman dystopique décrivant une société totalitaire contrôlée par Big Brother.',
                                'isbn'    => '9780451524935',
                                '_links'  => [
                                    'self'   => 'http://127.0.0.1:8000/api/v1/books/1',
                                    'update' => 'http://127.0.0.1:8000/api/v1/books/1',
                                    'delete' => 'http://127.0.0.1:8000/api/v1/books/1',
                                    'all'    => 'http://127.0.0.1:8000/api/v1/books',
                                ],
                            ],
                            [
                                'title'   => 'Dune',
                                'author'  => 'FRANK HERBERT',
                                'summary' => 'Épopée de science-fiction centrée sur la planète Arrakis.',
                                'isbn'    => '9780441013593',
                                '_links'  => [
                                    'self'   => 'http://127.0.0.1:8000/api/v1/books/2',
                                    'update' => 'http://127.0.0.1:8000/api/v1/books/2',
                                    'delete' => 'http://127.0.0.1:8000/api/v1/books/2',
                                    'all'    => 'http://127.0.0.1:8000/api/v1/books',
                                ],
                            ],
                        ],
                        'links' => [
                            'first' => 'http://127.0.0.1:8000/api/v1/books?page=1',
                            'last'  => 'http://127.0.0.1:8000/api/v1/books?page=2',
                            'prev'  => null,
                            'next'  => 'http://127.0.0.1:8000/api/v1/books?page=2',
                        ],
                        'meta' => [
                            'current_page' => 1,
                            'from'         => 1,
                            'last_page'    => 2,
                            'per_page'     => 2,
                            'to'           => 2,
                            'total'        => 3,
                        ],
                    ],
                ),
            ),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return BookResource::collection(Book::paginate(2));
    }

    #[OA\Post(
        path: '/books',
        summary: 'Créer un livre',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'Authorization', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'Bearer <token>')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'author', 'summary', 'isbn'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: 'Le Petit Prince'),
                    new OA\Property(property: 'author', type: 'string', minLength: 3, maxLength: 100, example: 'Antoine de Saint-Exupéry'),
                    new OA\Property(property: 'summary', type: 'string', minLength: 10, maxLength: 500, example: "Un aviateur rencontre un petit prince venu d'une autre planète."),
                    new OA\Property(property: 'isbn', type: 'string', minLength: 13, maxLength: 13, example: '9782070408504'),
                ],
            ),
        ),
        tags: ['Livres'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Livre créé',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'title'   => 'Le Petit Prince',
                            'author'  => 'ANTOINE DE SAINT-EXUPÉRY',
                            'summary' => "Un aviateur rencontre un petit prince venu d'une autre planète.",
                            'isbn'    => '9782070408504',
                            '_links'  => [
                                'self'   => 'http://127.0.0.1:8000/api/v1/books/4',
                                'update' => 'http://127.0.0.1:8000/api/v1/books/4',
                                'delete' => 'http://127.0.0.1:8000/api/v1/books/4',
                                'all'    => 'http://127.0.0.1:8000/api/v1/books',
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(example: ['message' => 'Unauthenticated.']),
            ),
            new OA\Response(
                response: 422,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    example: ['message' => 'The isbn has already been taken.', 'errors' => ['isbn' => ['The isbn has already been taken.']]],
                ),
            ),
        ],
    )]
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

    #[OA\Get(
        path: '/books/{book}',
        summary: 'Afficher un livre (mis en cache 60 min)',
        tags: ['Livres'],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Livre trouvé',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'title'   => '1984',
                            'author'  => 'GEORGE ORWELL',
                            'summary' => 'Roman dystopique décrivant une société totalitaire contrôlée par Big Brother.',
                            'isbn'    => '9780451524935',
                            '_links'  => [
                                'self'   => 'http://127.0.0.1:8000/api/v1/books/1',
                                'update' => 'http://127.0.0.1:8000/api/v1/books/1',
                                'delete' => 'http://127.0.0.1:8000/api/v1/books/1',
                                'all'    => 'http://127.0.0.1:8000/api/v1/books',
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Livre introuvable',
                content: new OA\JsonContent(example: ['message' => 'No query results for model [App\\Models\\Book] 99']),
            ),
        ],
    )]
    public function show(Book $book): BookResource
    {
        $attributes = Cache::remember("book-{$book->id}", 3600, fn () => $book->getAttributes());

        return new BookResource((new Book)->setRawAttributes($attributes));
    }

    #[OA\Patch(
        path: '/books/{book}',
        summary: 'Mettre à jour un livre (partiel)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'Authorization', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'Bearer <token>')),
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: '1984'),
                    new OA\Property(property: 'author', type: 'string', minLength: 3, maxLength: 100, example: 'George Orwell'),
                    new OA\Property(property: 'summary', type: 'string', minLength: 10, maxLength: 500, example: 'Roman dystopique...'),
                    new OA\Property(property: 'isbn', type: 'string', minLength: 13, maxLength: 13, example: '9780451524935'),
                ],
            ),
        ),
        tags: ['Livres'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Livre mis à jour',
                content: new OA\JsonContent(
                    example: [
                        'data' => [
                            'title'   => '1984',
                            'author'  => 'GEORGE ORWELL',
                            'summary' => 'Roman dystopique décrivant une société totalitaire contrôlée par Big Brother.',
                            'isbn'    => '9780451524935',
                            '_links'  => [
                                'self'   => 'http://127.0.0.1:8000/api/v1/books/1',
                                'update' => 'http://127.0.0.1:8000/api/v1/books/1',
                                'delete' => 'http://127.0.0.1:8000/api/v1/books/1',
                                'all'    => 'http://127.0.0.1:8000/api/v1/books',
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(example: ['message' => 'Unauthenticated.']),
            ),
            new OA\Response(
                response: 404,
                description: 'Livre introuvable',
                content: new OA\JsonContent(example: ['message' => 'No query results for model [App\\Models\\Book] 99']),
            ),
            new OA\Response(
                response: 422,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    example: ['message' => 'The isbn field must be 13 characters.', 'errors' => ['isbn' => ['The isbn field must be 13 characters.']]],
                ),
            ),
        ],
    )]
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

    #[OA\Delete(
        path: '/books/{book}',
        summary: 'Supprimer un livre',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'Authorization', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'Bearer <token>')),
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        tags: ['Livres'],
        responses: [
            new OA\Response(response: 204, description: 'Livre supprimé — aucun contenu'),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(example: ['message' => 'Unauthenticated.']),
            ),
            new OA\Response(
                response: 404,
                description: 'Livre introuvable',
                content: new OA\JsonContent(example: ['message' => 'No query results for model [App\\Models\\Book] 99']),
            ),
        ],
    )]
    public function destroy(Book $book): Response
    {
        $book->delete();
        Cache::forget("book-{$book->id}");

        return response()->noContent();
    }
}
