<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityHistory extends Model
{
    use HasFactory;

    protected $table = 'activity_histories';

    protected $fillable = [
        'activity_id',
        'user_id',
        'action',      
        'field',       // Usamos 'field' en lugar de 'campo_modificado'
        'old_value',   // Usamos 'old_value' en lugar de 'valor_anterior'
        'new_value',   // Usamos 'new_value' en lugar de 'valor_nuevo'
        'details',
        'comentario'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}