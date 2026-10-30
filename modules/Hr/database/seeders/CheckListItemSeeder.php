<?php

namespace Modules\Hr\Database\Seeders;

use Modules\Hr\Models\ChecklistItem;
use Illuminate\Database\Seeder;

class CheckListItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = config('samples.sampleData.checkListsItems');

        foreach ($items as $item) {
            ChecklistItem::create([
                'name' => $item['name'],
                'order' => $item['order'],
            ]);
        }
    }
}
