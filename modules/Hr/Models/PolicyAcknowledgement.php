<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer's "read and agree" on one policy document. One row per
 * volunteer and document; it keeps the version of the document that was read.
 * Only PolicyAcknowledgements writes it.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $policy_document_id
 * @property CarbonImmutable $acknowledged_at
 * @property CarbonImmutable|null $document_modified_at
 */
class PolicyAcknowledgement extends Model
{
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'document_modified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<PolicyDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PolicyDocument::class, 'policy_document_id');
    }
}
