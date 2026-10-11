<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public const NAMES = ['General', 'Hardware', 'Software', 'Network', 'Billing', 'Account Access'];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
