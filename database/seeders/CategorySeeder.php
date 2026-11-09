<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Malawi' => false,
            'Politics' => false,
            'Africa' => false,
            'World' => false,
            'International' => true,
            'Business' => true,
            'Health' => false,
            'Sports' => false,
            'Entertainment' => true,
            'Technology' => true,
            'Jobs & Internships' => true,
            'Culture' => false,
            'Opinion' => false,
        ];

        foreach ($categories as $name => $showOnHomepage) {
            $slug = str($name)->slug()->toString();
            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'in_menu' => true,
                    'show_on_homepage' => $showOnHomepage,
                    'position' => array_search($name, array_keys($categories), true),
                ],
            );
        }
    }
}
