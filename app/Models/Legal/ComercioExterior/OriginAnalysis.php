<?php

namespace App\Models\Legal\ComercioExterior;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OriginAnalysis extends Model
{
    protected $fillable = [
        'bom_id',
        'part_number',
        'fg_fraction',
        'fg_price_usd',
        'non_orig_cost_usd',
        'rvc_percentage',
        'rvc_threshold',
        'cc_complies',
        'origin_criterion',
        'qualifies',
        'applicable_rule',
        'copilot_response',
        'analyst_id',
        'valid_until',
    ];

    protected $casts = [
        'copilot_response'  => 'array',
        'cc_complies'       => 'boolean',
        'qualifies'         => 'boolean',
        'analyzed_at'       => 'datetime',
        'valid_until'       => 'date',
        'fg_price_usd'      => 'decimal:6',
        'non_orig_cost_usd' => 'decimal:6',
        'rvc_percentage'    => 'decimal:2',
        'rvc_threshold'     => 'decimal:2',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }
}
