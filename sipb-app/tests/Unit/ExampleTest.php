<?php

namespace Tests\Unit;

use App\Models\FoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_found_item(): void
    {
        $user = User::factory()->create();
        $item = FoundItem::create([
            'name' => 'Test Item',
            'category' => 'Elektronik',
            'description' => 'A test item',
            'location' => 'Kantin',
            'found_at' => now(),
            'photo_url' => 'https://example.com/photo.jpg',
            'status' => FoundItem::STATUS_AVAILABLE,
            'published_at' => now(),
            'managed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('found_items', ['name' => 'Test Item']);
        $this->assertEquals('tersedia', $item->status);
    }
}
