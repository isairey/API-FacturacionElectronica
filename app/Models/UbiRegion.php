<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbiRegion extends Model
{
    protected $table = 'ubi_regiones';
    
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'nombre'
    ];
    
    public function provincias(): HasMany
    {
        return $this->hasMany(UbiProvincia::class, 'region_id');
    }
    
    public function distritos(): HasMany
    {
        return $this->hasMany(UbiDistrito::class, 'region_id');
    }
}
