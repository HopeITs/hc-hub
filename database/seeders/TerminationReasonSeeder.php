<?php

namespace Database\Seeders;

use App\Models\TerminationReason;
use Illuminate\Database\Seeder;

class TerminationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
        
            [
                'title_en' => 'Resignation due to travel',
                'title_ar' => 'استقالات بسبب السفر',
            ],
            [
                'title_en' => 'Personal resignation (commitments - job opportunities ..)',
                'title_ar' => 'استقالات خاصة (التزامات - فرص عمل ..)',
            ],
            [
                'title_en' => 'Apology / Weak evaluation',
                'title_ar' => 'اعتذار/تقييم ضعيف',
            ],
            [
                'title_en' => 'Contract expired (contract + project)',
                'title_ar' => 'انتهاء عقد ( عقد+ مشروع)',
            ],
        ];

        foreach ($reasons as $reason) {
            TerminationReason::create($reason);
        }
    }
}
