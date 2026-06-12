<?php

namespace App\Models\Legal\ComercioExterior;

use Illuminate\Database\Eloquent\Model;

class UsmcaCertData extends Model
{
    protected $table = 'usmca_cert_data';

    protected $fillable = ['bom_id', 'cert_data'];

    protected $casts = ['cert_data' => 'array'];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }
}
