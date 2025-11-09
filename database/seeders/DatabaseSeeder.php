<?php

namespace Database\Seeders;

use App\Models\ArticleSource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        if (!User::first()) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@innoscripta.com',
            ]);
            $user->preferences()->create([
                'preferred_sources' => [1, 2, 3], // Source IDs
                'preferred_categories' => ['technology', 'business'],
                'preferred_authors' => [],
            ]);
        }


        ArticleSource::updateOrCreate([
            'name' => 'guardian',
        ],
            [
                'api_url' => 'https://content.guardianapis.com',
                'api_key' => '39940a7b-6636-4a7b-a732-a419454a647d',
                'fallback_api_key' => 'c7609952-88d7-4a5c-befe-fe6346f51801',
                'is_active' => true,
            ]);

        ArticleSource::updateOrCreate([
            'name' => 'nytimes',
        ],
            [
                'api_url' => 'https://api.nytimes.com',
                'api_key' => 'ief8OcILGDCjy4tJYTAAksCAniljiNpb',
                'fallback_api_key' => 'e2MNVC4R7wD1x9a0N9mAWr8CwFZPrGXj',
                'is_active' => true,
            ]);

        ArticleSource::updateOrCreate([
            'name' => 'newsapi',
        ],
            [
                'api_url' => 'https://newsapi.org/v2',
                'api_key' => 'a3a990fc6223472b8c147b87f238addc',
                'fallback_api_key' => '1e6ec555ec514007a70492720e893ed5',
                'is_active' => true,
            ]);
    }
}
