<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Configuracion extends Model
{
    use Auditable;

    protected $table = 'configuraciones';

    protected $fillable = [
        'empresa_nombre',
        'empresa_subtitulo',
        'empresa_propietario',
        'empresa_direccion',
        'logo_path',
    ];

    private static ?self $instanciaActual = null;

    /**
     * Fila única de configuración global, creada si todavía no existe.
     * Memoizada por request: se llama varias veces por página (header,
     * sidebar, etc.) y es siempre la misma fila.
     */
    public static function actual(): self
    {
        if (static::$instanciaActual !== null) {
            return static::$instanciaActual;
        }

        return static::$instanciaActual = static::query()->firstOrCreate([]);
    }

    /**
     * Subtítulo mostrado en los PDF (guía, orden de pago, recibo) debajo del
     * título del documento. Si nunca se configuró, cae a un default genérico
     * en vez de dejar el renglón vacío.
     */
    public function subtituloPdf(): string
    {
        return $this->empresa_subtitulo ?: 'Transporte de cargas';
    }

    /**
     * URL pública del logo para usar en pantalla (sidebar, etc).
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Logo como data URI (base64) para incrustar en los PDF: dompdf no
     * depende así del symlink de storage, que en algunos hostings no resuelve
     * (mismo gotcha ya visto con las fotos de producto de otros clientes).
     */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $contenido = Storage::disk('public')->get($this->logo_path);
        $mime = Storage::disk('public')->mimeType($this->logo_path);

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }
}
