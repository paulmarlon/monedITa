<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    /**
     * Muestra el formulario de configuración general del sistema.
     */
    public function index()
    {
        $configuracion = Configuracion::firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Monedita',
                'descripcion' => 'Sistema de Gestión y Control Institucional'
            ]
        );

        return view('configuracion.index', compact('configuracion'));
    }

    /**
     * Actualiza los datos y el logotipo de la configuración.
     */
    public function update(Request $request, Configuracion $configuracion)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'nombre.required' => 'El nombre del sistema es obligatorio.',
            'correo.email' => 'El formato del correo electrónico no es válido.',
            'web.url' => 'El sitio web debe ser una URL válida (ej: https://tuweb.com).',
            'logo.image' => 'El archivo cargado debe ser una imagen.',
            'logo.mimes' => 'El logo debe tener formato: jpeg, png, jpg o svg.',
            'logo.max' => 'El logo no debe superar los 2MB de peso.',
        ]);

        $data = $request->except('logo');

        // Manejo de la subida del Logotipo
        // Manejo de la subida del Logotipo
        // Manejo de la subida del Logotipo
        if ($request->hasFile('logo')) {
            // Si ya existía un logo previo, lo eliminamos de S3 de forma segura
            if ($configuracion->logo) {
                $parsedUrl = parse_url($configuracion->logo, PHP_URL_PATH);
                $relativePath = preg_replace('/^\/storage\/v1\/object\/public\/[^\/]+\//', '', $parsedUrl);

                if ($relativePath) {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $s3 */
                    $s3 = Storage::disk('s3');
                    $s3->delete($relativePath);
                }
            }

            // Guardamos el nuevo archivo directamente en el disco S3
            $path = $request->file('logo')->store('tapita/logos', 's3');

            // Construimos explícitamente la URL pública web de Supabase (/object/public/)
            $baseUrl = rtrim(env('AWS_ENDPOINT'), '/s3');
            $bucket = env('AWS_BUCKET');

            $data['logo'] = "{$baseUrl}/object/public/{$bucket}/{$path}";
        }

        // Actualizamos el registro
        $configuracion->update($data);

        return redirect()->route('configuracion.index')
            ->with('mensaje', '¡Configuración actualizada correctamente!')
            ->with('icon', 'success');
    }
}
