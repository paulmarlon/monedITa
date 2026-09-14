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
        // Buscamos el registro con ID 1, o lo creamos por defecto si la tabla está vacía
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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048', // Máximo 2MB
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
        if ($request->hasFile('logo')) {
            // Si ya existía un logo previo en S3, lo eliminamos para no acumular basura
            if ($configuracion->logo && Storage::disk('s3')->exists($configuracion->logo)) {
                Storage::disk('s3')->delete($configuracion->logo);
            }

            // Guardamos el nuevo archivo en el bucket de Supabase dentro de tapita/logos
            $path = $request->file('logo')->store('tapita/logos', 's3');
            $data['logo'] = $path;
        }

        // Actualizamos el registro
        $configuracion->update($data);

        return redirect()->route('configuracion.index')
            ->with('mensaje', '¡Configuración actualizada correctamente!')
            ->with('icon', 'success');
    }
}
