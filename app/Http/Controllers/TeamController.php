<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Ciclo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * Constructor para aplicar los middlewares de permisos de Spatie.
     */
    public function __construct()
    {
        $this->middleware('can:ver_teams')->only(['index', 'show', 'trash']);
        $this->middleware('can:crear_teams')->only(['create', 'store']);
        $this->middleware('can:editar_teams')->only(['edit', 'update', 'restore']);
        $this->middleware('can:eliminar_teams')->only(['destroy']);
    }

    /**
     * Muestra la lista de equipos activos con su ciclo relacionado.
     */
    public function index()
    {
        $teams = Team::with('ciclo')->get();
        return view('teams.index', compact('teams'));
    }

    /**
     * Muestra el formulario para crear un nuevo equipo.
     */
    public function create()
    {
        $ciclos = Ciclo::where('estado', 'ACTIVO')->get();
        return view('teams.create', compact('ciclos'));
    }

    /**
     * Almacena un nuevo equipo en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => ['required', 'string', 'max:255'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'logo'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = $request->only(['nombre', 'ciclo_id']);

        if ($request->hasFile('logo')) {
            // Guardamos el archivo en S3
            $path = $request->file('logo')->store('tapita/teams_logos', 's3');

            // Construimos explícitamente la URL pública web de Supabase (/object/public/)
            $baseUrl = rtrim(env('AWS_ENDPOINT'), '/s3');
            $bucket = env('AWS_BUCKET');

            $data['logo'] = "{$baseUrl}/object/public/{$bucket}/{$path}";
        }

        Team::create($data);

        return redirect()->route('teams.index')->with('success', '¡Equipo creado con éxito!');
    }

    /**
     * Muestra el formulario para editar un equipo existente.
     */
    public function edit(Team $team)
    {
        $ciclos = Ciclo::all();
        return view('teams.edit', compact('team', 'ciclos'));
    }

    /**
     * Actualiza el equipo en la base de datos.
     */
    public function update(Request $request, Team $team)
    {
        $request->validate([
            'nombre'   => ['required', 'string', 'max:255'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'logo'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = $request->only(['nombre', 'ciclo_id']);

        if ($request->hasFile('logo')) {
            // Guardamos el archivo en S3
            $path = $request->file('logo')->store('tapita/teams_logos', 's3');

            // Construimos explícitamente la URL pública web de Supabase (/object/public/)
            $baseUrl = rtrim(env('AWS_ENDPOINT'), '/s3');
            $bucket = env('AWS_BUCKET');

            $data['logo'] = "{$baseUrl}/object/public/{$bucket}/{$path}";
        }

        $team->update($data);

        return redirect()->route('teams.index')->with('success', '¡Equipo actualizado con éxito!');
    }

    /**
     * Envía un equipo a la papelera (SoftDelete).
     */
    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams.index')->with('success', '¡Equipo enviado a la papelera!');
    }

    /**
     * Muestra la papelera de equipos eliminados lógicamente.
     */
    public function trash()
    {
        $teams = Team::onlyTrashed()->with('ciclo')->get();
        return view('teams.trash', compact('teams'));
    }

    /**
     * Restaura un equipo previamente eliminado.
     */
    public function restore(int $id)
    {
        $team = Team::onlyTrashed()->findOrFail($id);
        $team->restore();
        return redirect()->route('teams.trash')->with('success', '¡Equipo restaurado con éxito!');
    }
}
