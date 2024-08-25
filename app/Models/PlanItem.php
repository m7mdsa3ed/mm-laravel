<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    protected $appends = [
        'transactions_total',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            PlanItemTransaction::class,
            'plan_item_id',
            'id',
            'id',
            'transaction_id'
        );
    }

    public function transactionsTotal(): Attribute
    {
        return Attribute::make(
            get: function () {
                $transactionsRelationLoaded = $this->relationLoaded('transactions');

                if ($transactionsRelationLoaded) {
                    return $this->transactions->sum('amount');
                }

                return 0;
            }
        );
    }
}
