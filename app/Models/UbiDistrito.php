<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UbiDistrito extends Model
{
    protected $table = 'ubi_distritos';
    
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'nombre',
        'info_busqueda',
        'provincia_id',
        'region_id'
    ];
    
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(UbiProvincia::class, 'provincia_id');
    }
    
    public function region(): BelongsTo
    {
        return $this->belongsTo(UbiRegion::class, 'region_id');
    }
}
