<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditLedger extends Model
{
    protected $fillable = [
        'workspace_id', 'type', 'amount', 'reason', 'ref_type', 'ref_id',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
