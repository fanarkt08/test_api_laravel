<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title'   => '1984',
                'author'  => 'George Orwell',
                'summary' => 'Roman dystopique décrivant une société totalitaire contrôlée par Big Brother.',
                'isbn'    => '9780451524935',
            ],
            [
                'title'   => 'Dune',
                'author'  => 'Frank Herbert',
                'summary' => 'Épopée de science-fiction centrée sur la planète Arrakis et les enjeux autour de l\'épice.',
                'isbn'    => '9780441013593',
            ],
            [
                'title'   => 'Le Seigneur des Anneaux',
                'author'  => 'J.R.R. Tolkien',
                'summary' => 'Trilogie racontant la quête pour détruire l\'Anneau unique et vaincre Sauron.',
                'isbn'    => '9780544003415',
            ],
        ];

        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
