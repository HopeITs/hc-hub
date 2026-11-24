<?php

use Illuminate\Validation\ValidationException;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFormService;
use Modules\Hr\Models\ApplicationFormVersion;

/*
| The application form asks for the four names in Arabic and English, each in
| its own script, so a hire retypes none of them. The applicant record keeps
| one name, built from the Arabic first and last name.
*/

function publishedFormVersion(): ApplicationFormVersion
{
    return new ApplicationFormVersion(['status' => 'published', 'fields_json' => []]);
}

/** @return array<string, string> */
function nameAnswers(array $override = []): array
{
    return $override + [
        'first_name_ar' => 'رامي', 'last_name_ar' => 'تارك', 'father_name_ar' => 'جورج', 'mother_name_ar' => 'ماري',
        'first_name_en' => 'Rami', 'last_name_en' => 'Tarek', 'father_name_en' => 'George', 'mother_name_en' => 'Mary',
    ];
}

test('every form asks for the eight names, together, just before the email', function () {
    $keys = array_column(app(RecruitmentApplicationFormService::class)->normalizeFields([]), 'key');

    expect($keys)->toContain(...RecruitmentApplicationFormService::NAME_FIELDS)->not->toContain('full_name');

    $first = array_search('first_name_ar', $keys, true);
    expect(array_slice($keys, $first, 8))->toBe(RecruitmentApplicationFormService::NAME_FIELDS)
        ->and($keys[$first + 8])->toBe('email');
});

test('the applicant record keeps one name, from the Arabic first and last name', function () {
    expect(RecruitmentApplicationFormService::fullNameFrom(nameAnswers()))->toBe('رامي تارك')
        ->and(RecruitmentApplicationFormService::fullNameFrom(['first_name_ar' => '  رامي ', 'last_name_ar' => '']))->toBe('رامي');
});

test('each name is checked for its script', function () {
    $form = app(RecruitmentApplicationFormService::class);
    $others = ['email' => 'a@example.org', 'phone' => '0944123456'];

    expect(fn () => $form->validateSubmission(publishedFormVersion(), nameAnswers(['first_name_ar' => 'Rami']) + $others))
        ->toThrow(ValidationException::class);

    expect(fn () => $form->validateSubmission(publishedFormVersion(), nameAnswers(['last_name_en' => 'تارك']) + $others))
        ->toThrow(ValidationException::class);

    try {
        $form->validateSubmission(publishedFormVersion(), nameAnswers(['first_name_ar' => 'Rami']) + $others);
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('answers.first_name_ar')
            ->and($exception->errors()['answers.first_name_ar'][0])->toBe(__('hr_recruitment.careers.errors.name_arabic'));
    }
});
