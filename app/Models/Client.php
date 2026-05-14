<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function boletas(): HasMany
    {
        return $this->hasMany(Boleta::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class);
    }

    public function dispatchGuides(): HasMany
    {
        return $this->hasMany(DispatchGuide::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDocumentTypeNameAttribute(): string
    {
        return match($this->tipo_documento) {
            '1' => 'DNI',
            '4' => 'Carnet de Extranjería',
            '6' => 'RUC',
            '0' => 'DOC.TRIB.NO.DOM.SIN.RUC',
            default => 'Desconocido'
        };
    }

    public function getFullDocumentAttribute(): string
    {
        return $this->tipo_documento . '-' . $this->numero_documento;
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByDocument($query, string $tipoDocumento, string $numeroDocumento)
    {
        return $query->where('tipo_documento', $tipoDocumento)
                    ->where('numero_documento', $numeroDocumento);
    }
}