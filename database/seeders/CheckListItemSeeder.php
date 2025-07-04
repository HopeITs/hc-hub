<?php

namespace Database\Seeders;

use App\Models\CheckListItem;
use Illuminate\Database\Seeder;

class CheckListItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = config('samples.sampleData.checkListsItems');

        foreach ($items as $item) {
            CheckListItem::create([
                'name' => $item['name'],
                'order' => $item['order'],
            ]);
        }
    }
}
