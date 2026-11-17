<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    /**
     * Liste des structures de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Structure::where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $structures = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Structures retrieved successfully',
            'data' => StructureResource::collection($structures),
            'meta' => [
                'current_page' => $structures->currentPage(),
                'last_page' => $structures->lastPage(),
                'total' => $structures->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher une structure de l'utilisateur connecté.
     */
    public function show(Request $request, $id)
    {
        $structure = Structure::where('inserted_by', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$structure) {
            return response()->json([
                'message' => 'Structure not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Structure retrieved successfully',
            'data' => new StructureResource($structure),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer une structure.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nom.required' => 'Le nom de la structure est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format jpeg, png, jpg, gif ou webp.',
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

        $data = [
            'user_id' => $user->id,
            'nom' => $request->nom,
            'sigle' => $request->sigle,
            'description' => $request->description,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'active' => true,
            'deleted' => false,
            'inserted_by' => $user->id,
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('structures', 'public');
        }

        $structure = Structure::create($data);

        return response()->json([
            'message' => 'Structure created successfully',
            'data' => new StructureResource($structure),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier une structure.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $structure = Structure::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$structure) {
            return response()->json([
                'message' => 'Structure not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'active' => 'nullable|boolean',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'telephone.required' => 'Le téléphone est obligatoire.',
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

        $structure->update([
            'nom' => $request->nom,
            'sigle' => $request->sigle,
            'description' => $request->description,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'active' => $request->active ?? $structure->active,
            'updated_by' => $user->id,
        ]);

        if ($request->hasFile('logo')) {
            if ($structure->logo) {
                Storage::disk('public')->delete($structure->logo);
            }

            $structure->update([
                'logo' => $request->file('logo')->store('structures', 'public'),
            ]);
        }

        return response()->json([
            'message' => 'Structure updated successfully',
            'data' => new StructureResource($structure),
            'status' => 200,
        ], 200);
    }

    /**
     * Suppression logique.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $structure = Structure::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$structure) {
            return response()->json([
                'message' => 'Structure not found',
                'status' => 404,
            ], 404);
        }

        if ($structure->evenements()->where('deleted', false)->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette structure car elle possède encore des événements.',
                'status' => 409,
            ], 409);
        }

        $structure->update([
            'deleted' => true,
            'active' => false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Structure deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche des structures.
     */
    public function search(Request $request)
    {
        $user = $request->user();

        $query = Structure::where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Structures retrieved successfully',
            'data' => StructureResource::collection($query->latest()->get()),
            'status' => 200,
        ], 200);
    }
}



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StructureResource;
use App\Models\Structure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

