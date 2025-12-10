<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ContractType extends Model
{
    use HasFactory, HasTranslation, LogsActivity;

    protected $fillable = [
        'type',
        'place',
        'name_en',
        'name_ar'
    ];

    protected $casts = [
        'type'  => 'integer',
        'place' => 'integer',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'contract_type_id');
    }

    public function getCategoryAttribute(): string
    {
        $map = config('contracts.map_by_id');

        return $map[$this->id] ?? 'annual';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract_type')
            ->logOnly([
                'type',
                'place',
                'name_en',
                'name_ar',
                'name',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
