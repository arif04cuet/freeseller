<?php

namespace Database\Seeders;

use App\Enum\AddressType;
use App\Models\Address;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpazilaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        //DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $csv = base_path('database/seeders/csv/upazilas.csv');

        $csvFile = fopen($csv, 'r');
        $headers = fgetcsv($csvFile); // Read and ignore the header row

        $batchSize = 100;
        while (!feof($csvFile)) {
            $batch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $row = fgetcsv($csvFile);
                if ($row === false) {
                    break;
                }
                $batch[] = $row;
            }

            if (!empty($batch)) {

                foreach ($batch as $upazila) {

                    $districtName = trim($upazila[0]) . ' জেলা';
                    $upazilaName = trim($upazila[1]);

                    $district  = Address::where([
                        'name' => $districtName,
                        'type' => AddressType::District->value,
                    ])->first();

                    if ($district) {
                        Address::create([
                            'name' => $upazilaName,
                            'type' => AddressType::Upazila->value,
                            'parent_id' => $district->id,
                        ]);
                    }
                }
            }
        }
    }
}
