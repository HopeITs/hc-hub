<?php

use App\Support\ArabicText;

test('search folding ignores tashkeel, tatweel and letter variants', function (string $stored, string $typed) {
    expect(ArabicText::containsTyped($stored, $typed))->toBeTrue();
})->with([
    'tashkeel' => ['مَدْرَسَة', 'مدرسة'],
    'taa marbuta' => ['مدرسة', 'مدرسه'],
    'alef forms' => ['إغاثة الآبار', 'اغاثه الابار'],
    'alef maqsura' => ['مستشفى', 'مستشفي'],
    'tatweel' => ['برامـــج', 'برامج'],
    'wrong keyboard layout' => ['Programs', 'حقخلقشةس'],
]);

test('search folding still rejects a different word', function () {
    expect(ArabicText::containsTyped('مدرسة', 'مستشفى'))->toBeFalse();
});

test('each word is found on its own, with «ال» optional', function () {
    expect(ArabicText::containsTyped('مؤسسة الإغاثة', 'موسسه اغاثه'))->toBeTrue()
        ->and(ArabicText::containsTyped('مؤسسة الإغاثة', 'الاغاثه موسسه'))->toBeTrue()
        ->and(ArabicText::containsTyped('مؤسسة الإغاثة', 'موسسه مدرسه'))->toBeFalse();
});
