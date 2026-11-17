<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CategorieResource;
use App\Models\Categorie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CategorieController extends Controller
{
    /**
     * Liste des catégories de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Categorie::where('deleted', false)
            ->where('inserted_by', $user->id);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('nom', 'asc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => CategorieResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher une catégorie.
     */
    public function show(Request $request, $id)
    {
        $categorie = Categorie::where('inserted_by', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Category not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => new CategorieResource($categorie),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer une catégorie.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'active.boolean' => 'L\'état actif doit être un booléen.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Format d\'image non autorisé.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $user = $request->user();

        $categorie = Categorie::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'active' => $request->active ?? true,
            'deleted' => false,
            'inserted_by' => $user->id,
        ]);

        if ($request->hasFile('image')) {
            $filePath = $request->file('image')->store('categories', 'public');

            $categorie->update([
                'image' => $filePath,
            ]);
        }

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategorieResource($categorie),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier une catégorie.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $categorie = Categorie::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Category not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'active.boolean' => 'L\'état actif doit être un booléen.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Format d\'image non autorisé.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $categorie->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'active' => $request->active ?? $categorie->active,
            'updated_by' => $user->id,
        ]);

        if ($request->hasFile('image')) {
            if ($categorie->image) {
                Storage::disk('public')->delete($categorie->image);
            }

            $filePath = $request->file('image')->store('categories', 'public');

            $categorie->update([
                'image' => $filePath,
            ]);
        }

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategorieResource($categorie),
            'status' => 200,
        ], 200);
    }

    /**
     * Supprimer logiquement une catégorie.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $categorie = Categorie::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$categorie) {
            return response()->json([
                'message' => 'Category not found',
                'status' => 404,
            ], 404);
        }

        if ($categorie->jeux()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car des jeux lui sont associés.',
                'status' => 409,
            ], 409);
        }

        $categorie->update([
            'deleted' => true,
            'active' => false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Category deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche des catégories.
     */
    public function search(Request $request)
    {
        $user = $request->user();

        $query = Categorie::where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => CategorieResource::collection($query->get()),
            'status' => 200,
        ], 200);
    }
}