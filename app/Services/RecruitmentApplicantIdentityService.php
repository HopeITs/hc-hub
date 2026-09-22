<?php

namespace App\Services;

use App\Models\RecruitmentApplicant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Normalizes an applicant's identity and finds-or-creates their profile.
 *
 * A Candidate/Applicant is the person, reusable across campaigns; this is the
 * one place that decides whether two submissions are "the same person" so
 * that rule cannot drift between the public form and any future import or
 * admin-entry path (recruitment-execution-plan-ar.md, Phase 2 Task 1).
 *
 * // TODO (Business Rule): matching is on normalized email only. The plan
 * // explicitly forbids merging on name alone, but says nothing about phone.
 * // Today two submissions with the same phone but different emails become
 * // two separate applicants — safer than a wrong merge, but the business
 * // team may want a "possible duplicate" flag instead once they decide.
 */
class RecruitmentApplicantIdentityService
{
    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Digits and a leading "+" only, after folding Arabic-Indic digits to
     * Western ones — the same normalization the rest of the app uses for
     * Arabic input (see en_digits() in app/Support/helpers.php).
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', en_digits($phone));

        return $digits === '' ? null : $digits;
    }

    public function identityKeyHash(string $emailNormalized): string
    {
        return hash('sha256', $emailNormalized);
    }

    /**
     * Find the applicant this email already belongs to, or create one.
     *
     * Locked so two submissions racing on the same email cannot each create
     * their own applicant row — the unique index on identity_key_hash is the
     * last net under that, but the lock avoids relying on catching that
     * constraint violation on the common path.
     *
     * @param  array{full_name: string, email: string, phone?: ?string, city_id?: ?int, source: string}  $data
     */
    public function findOrCreate(array $data): RecruitmentApplicant
    {
        $emailNormalized = $this->normalizeEmail($data['email']);
        $phoneNormalized = $this->normalizePhone($data['phone'] ?? null);
        $hash = $this->identityKeyHash($emailNormalized);

        return DB::transaction(function () use ($data, $emailNormalized, $phoneNormalized, $hash) {
            $applicant = RecruitmentApplicant::query()
                ->where('identity_key_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($applicant !== null) {
                // TODO (Business Rule): later submissions overwrite name/phone
                // on the shared profile. Fine for an MVP where the applicant is
                // the one supplying the data; revisit if HR ever edits this
                // profile directly, since a later public submission would then
                // clobber their correction.
                $applicant->forceFill([
                    'full_name' => $data['full_name'],
                    'phone_normalized' => $phoneNormalized ?? $applicant->phone_normalized,
                    'city_id' => $data['city_id'] ?? $applicant->city_id,
                    'last_seen_at' => now(),
                ])->save();

                return $applicant;
            }

            return RecruitmentApplicant::query()->create([
                'full_name' => $data['full_name'],
                'email_normalized' => $emailNormalized,
                'phone_normalized' => $phoneNormalized,
                'identity_key_hash' => $hash,
                'city_id' => $data['city_id'] ?? null,
                'source' => $data['source'],
                'last_seen_at' => now(),
            ]);
        });
    }
}
