<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correlative extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'tipo_documento',
        'serie',
        'correlativo_actual',
    ];

    protected $casts = [
        'correlativo_actual' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getFormattedCorrelativeAttribute(): string
    {
        return str_pad((string)$this->correlativo_actual, 6, '0', STR_PAD_LEFT);
    }

    public function getNumeroCompletoAttribute(): string
    {
        return $this->serie . '-' . $this->getFormattedCorrelativeAttribute();
    }
}