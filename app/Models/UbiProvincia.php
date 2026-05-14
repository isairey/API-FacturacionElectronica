<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbiProvincia extends Model
{
    protected $table = 'ubi_provincias';
    
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'nombre',
        'region_id'
    ];
    
    public function region(): BelongsTo
    {
        return $this->belongsTo(UbiRegion::class, 'region_id');
    }
    
    public function distritos(): HasMany
    {
        return $this->hasMany(UbiDistrito::class, 'provincia_id');
    }
}
