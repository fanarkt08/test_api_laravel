<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_is_created_in_database_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', [
                'title'   => 'Le Petit Prince',
                'author'  => 'Antoine de Saint-Exupéry',
                'summary' => "Un aviateur rencontre un petit prince venu d'une autre planète.",
                'isbn'    => '9782070408504',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['isbn' => '9782070408504']);
    }

    public function test_book_is_not_created_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/books', [
                'title'   => 'AB',
                'author'  => 'Antoine de Saint-Exupéry',
                'summary' => "Un aviateur rencontre un petit prince venu d'une autre planète.",
                'isbn'    => '9782070408511',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('books', ['isbn' => '9782070408511']);
    }

    public function test_book_is_not_created_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title'   => 'Le Petit Prince',
            'author'  => 'Antoine de Saint-Exupéry',
            'summary' => "Un aviateur rencontre un petit prince venu d'une autre planète.",
            'isbn'    => '9782070408528',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('books', ['isbn' => '9782070408528']);
    }
}
