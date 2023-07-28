<?php

namespace Database\Seeders;

use App\Enum\OptionType;
use App\Enum\OptionValueType;
use App\Models\Option;
use App\Models\ProductType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //create product type

        $saree = ProductType::create([
            'name' => 'Sharee',
            'code' => 'sharee',
            'is_varient_price' => false
        ]);
        // for products

        $option = Option::create([
            'name' => 'Has blouse piece?',
            'field_for' => OptionType::Product->value,
            'field_type' => OptionValueType::Boolean->value
        ]);

        $saree->options()->save($option);
    }
}
