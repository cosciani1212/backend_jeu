<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\EquipeResource;
use App\Models\Equipe;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class EquipeController extends Controller
{
    /**
     * Liste des équipes de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Equipe::where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $equipes = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'data' => EquipeResource::collection($equipes),
            'meta' => [
                'current_page' => $equipes->currentPage(),
                'last_page' => $equipes->lastPage(),
                'total' => $equipes->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher une équipe.
     */
    public function show(Request $request, $id)
    {
        $equipe = Equipe::where('inserted_by', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$equipe) {
            return response()->json([
                'message' => 'Team not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Team retrieved successfully',
            'data' => new EquipeResource($equipe),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer une équipe.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:equipes,code',
            'description' => 'nullable|string',
            'statut' => 'nullable|in:active,inactive',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nom.required' => 'Le nom de l\'équipe est obligatoire.',
            'code.required' => 'Le code de l\'équipe est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Format de logo non autorisé.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $user = $request->user();

        $equipe = Equipe::create([
            'nom' => $request->nom,
            'code' => $request->code,
            'description' => $request->description,
            'statut' => $request->statut ?? 'active',
            'active' => true,
            'deleted' => false,
            'inserted_by' => $user->id,
        ]);

        if ($request->hasFile('logo')) {
            $equipe->update([
                'logo' => $request->file('logo')->store('equipes', 'public'),
            ]);
        }

        return response()->json([
            'message' => 'Team created successfully',
            'data' => new EquipeResource($equipe),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier une équipe.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $equipe = Equipe::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$equipe) {
            return response()->json([
                'message' => 'Team not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('equipes', 'code')->ignore($equipe->id),
            ],
            'description' => 'nullable|string',
            'statut' => 'nullable|in:active,inactive',
            'active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nom.required' => 'Le nom de l\'équipe est obligatoire.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'statut.in' => 'Le statut est invalide.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Format de logo non autorisé.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $equipe->update([
            'nom' => $request->nom,
            'code' => $request->code,
            'description' => $request->description,
            'statut' => $request->statut ?? $equipe->statut,
            'active' => $request->active ?? $equipe->active,
            'updated_by' => $user->id,
        ]);

        if ($request->hasFile('logo')) {
            if ($equipe->logo) {
                Storage::disk('public')->delete($equipe->logo);
            }

            $equipe->update([
                'logo' => $request->file('logo')->store('equipes', 'public'),
            ]);
        }

        return response()->json([
            'message' => 'Team updated successfully',
            'data' => new EquipeResource($equipe),
            'status' => 200,
        ], 200);
    }

    /**
     * Supprimer logiquement une équipe.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $equipe = Equipe::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$equipe) {
            return response()->json([
                'message' => 'Team not found',
                'status' => 404,
            ], 404);
        }

        $equipe->update([
            'deleted' => true,
            'active' => false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Team deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche des équipes.
     */
    public function search(Request $request)
    {
        $user = $request->user();

        $query = Equipe::where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'data' => EquipeResource::collection($query->latest()->get()),
            'status' => 200,
        ], 200);
    }
}

