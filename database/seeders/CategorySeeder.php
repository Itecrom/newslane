<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Malawi', 'Politics', 'Africa', 'World', 'Business', 'Health', 'Sports', 'Culture', 'Opinion'];

        foreach ($categories as $position => $name) {
            Category::firstOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'in_menu' => true, 'position' => $position],
            );
        }
    }
}
