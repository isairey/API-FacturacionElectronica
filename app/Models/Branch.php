<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'codigo',
        'nombre',
        'direccion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
        'telefono',
        'email',
        'series_factura',
        'series_boleta',
        'series_nota_credito',
        'series_nota_debito',
        'series_guia_remision',
        'activo',
    ];

    protected $casts = [
        'series_factura' => 'array',
        'series_boleta' => 'array',
        'series_nota_credito' => 'array',
        'series_nota_debito' => 'array',
        'series_guia_remision' => 'array',
        'activo' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function correlatives(): HasMany
    {
        return $this->hasMany(Correlative::class);
    }

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

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DailySummary::class);
    }

    public function voidedDocuments(): HasMany
    {
        return $this->hasMany(VoidedDocument::class);
    }

    public function getNextCorrelative(string $tipoDocumento, string $serie): string
    {
        $correlative = $this->correlatives()
            ->where('tipo_documento', $tipoDocumento)
            ->where('serie', $serie)
            ->lockForUpdate()
            ->first();

        if (!$correlative) {
            $correlative = $this->correlatives()->create([
                'tipo_documento' => $tipoDocumento,
                'serie' => $serie,
                'correlativo_actual' => 0,
            ]);
        }

        $correlative->increment('correlativo_actual');
        
        return str_pad((string)$correlative->correlativo_actual, 6, '0', STR_PAD_LEFT);
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}