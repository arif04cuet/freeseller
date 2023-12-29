<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryChildrenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootCat1 = Category::updateOrcreate(
            [
                'name' => 'নারী ও মেয়েদের ফ্যাশন',
            ],
            [
                'category_id' => null,
                'product_type_id' => 1,
                'is_system' => 1
            ]
        );

        Category::query()
            ->where('id', '!=', $rootCat1->id)
            ->update(['category_id' => $rootCat1->id]);
    }
}
