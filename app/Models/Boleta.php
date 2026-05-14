<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boleta extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'daily_summary_id',
        'tipo_documento',
        'serie',
        'correlativo',
        'numero_completo',
        'fecha_emision',
        'ubl_version',
        'tipo_operacion',
        'moneda',
        'metodo_envio',
        'valor_venta',
        'mto_oper_gravadas',
        'mto_oper_exoneradas',
        'mto_oper_inafectas',
        'mto_oper_gratuitas',
        'mto_igv_gratuitas',
        'mto_igv',
        'mto_base_ivap',
        'mto_ivap',
        'mto_isc',
        'mto_icbper',
        'total_impuestos',
        'sub_total',
        'mto_imp_venta',
        'detalles',
        'leyendas',
        'datos_adicionales',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'estado_sunat',
        'respuesta_sunat',
        'codigo_hash',
        'usuario_creacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'valor_venta' => 'decimal:2',
        'mto_oper_gravadas' => 'decimal:2',
        'mto_oper_exoneradas' => 'decimal:2',
        'mto_oper_inafectas' => 'decimal:2',
        'mto_oper_gratuitas' => 'decimal:2',
        'mto_igv_gratuitas' => 'decimal:2',
        'mto_igv' => 'decimal:2',
        'mto_isc' => 'decimal:2',
        'mto_icbper' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'mto_imp_venta' => 'decimal:2',
        'detalles' => 'array',
        'leyendas' => 'array',
        'datos_adicionales' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dailySummary(): BelongsTo
    {
        return $this->belongsTo(DailySummary::class);
    }

    public function getTipoDocumentoNameAttribute(): string
    {
        return 'Boleta de Venta Electrónica';
    }

    public function getEstadoSunatColorAttribute(): string
    {
        return match($this->estado_sunat) {
            'PENDIENTE' => 'warning',
            'ENVIADO' => 'info',
            'ACEPTADO' => 'success',
            'RECHAZADO' => 'danger',
            default => 'secondary'
        };
    }

    public function scopePending($query)
    {
        return $query->where('estado_sunat', 'PENDIENTE');
    }

    public function scopeAccepted($query)
    {
        return $query->where('estado_sunat', 'ACEPTADO');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_emision', [$startDate, $endDate]);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($boleta) {
            if (empty($boleta->numero_completo)) {
                $boleta->numero_completo = $boleta->serie . '-' . $boleta->correlativo;
            }
        });
    }
}