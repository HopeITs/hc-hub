<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;

/**
 * Enable, disable, and rotate an employee's digital ID token.
 *
 * Rotation is the important operation. A printed QR code that never changes is
 * a bearer credential in physical form: whoever photographs the card can load
 * the ID page indefinitely. Rotation issues a new token and instantly makes
 * every existing printed card inert, which is the response to a lost or
 * confiscated card. Disabling does the same without invalidating the token, for
 * a card that may come back.
 *
 * The token value is never written to the audit log — logging it would put the
 * credential in a table read by more people than can use the card.
 */
class DigitalIdManager
{
    public function rotate(Employee $employee): string
    {
        $token = Str::random(64);

        $employee->forceFill([
            'digital_id_token' => $token,
            'digital_id_enabled' => true,
            'digital_id_rotated_at' => now(),
            'digital_id_revoked_at' => null,
        ])->save();

        $this->record($employee, 'digital_id_rotated');

        return $token;
    }

    public function disable(Employee $employee): void
    {
        // Strict comparison: an Employee instance that was not selected with
        // this column reads null here, and `! null` would short-circuit a
        // revocation that must never be skipped.
        if ($employee->digital_id_enabled === false) {
            return;
        }

        $employee->forceFill([
            'digital_id_enabled' => false,
            'digital_id_revoked_at' => now(),
        ])->save();

        $this->record($employee, 'digital_id_disabled');
    }

    public function enable(Employee $employee): void
    {
        $employee->forceFill([
            'digital_id_enabled' => true,
            'digital_id_revoked_at' => null,
        ])->save();

        $this->record($employee, 'digital_id_enabled');
    }

    private function record(Employee $employee, string $event): void
    {
        Activity::useLog('security')
            ->performedOn($employee)
            // Deliberately no token value, old or new.
            ->withProperties(['employee_id' => $employee->id])
            ->log($event);
    }
}
