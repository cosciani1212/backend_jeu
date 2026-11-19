<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\JeuResource;
use App\Models\Jeu;
use Illuminate\Support\Facades\Validator;
use App\Models\Categorie;

class JeuController extends Controller
{
    /**
     * Liste des jeux de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Jeu::with(['categorie', 'evenement'])
            ->where('user_id', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        $jeus = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Jeux retrieved successfully',
            'data' => JeuResource::collection($jeus),
            'meta' => [
                'current_page' => $jeus->currentPage(),
                'last_page' => $jeus->lastPage(),
                'total' => $jeus->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche rapide de jeux.
     */
    public function search(Request $request)
    {
        $query = Jeu::with(['categorie', 'evenement'])
            ->where('deleted', false)
            ->where('active', true);

        if ($request->filled('q')) {
            $query->where('nom', 'like', '%' . $request->q . '%');
        }

        $jeus = $query->latest()->limit(20)->get();

        return response()->json([
            'message' => 'Search completed successfully',
            'data' => JeuResource::collection($jeus),
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher un jeu précis.
     */
    public function show(Request $request, $id)
    {
        $jeu = Jeu::with(['categorie', 'evenement'])
            ->where('user_id', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jeu) {
            return response()->json([
                'message' => 'Jeu not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Jeu retrieved successfully',
            'data' => new JeuResource($jeu),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer un nouveau jeu.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorie_id' => 'required|exists:categories,id',
            'evenement_id'=>'required|exists:evenements,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'nombre_joueurs' => 'nullable|integer|min:1',
            'nombre_equipes' => 'nullable|integer|min:1',
            'est_payant' => 'nullable|boolean',
            'montant_inscription' => 'nullable|numeric|min:0',
            'mode_competition' => 'nullable|string|max:255',
            'date_demarrage' => 'nullable|date',
            'date_limite_inscription' => 'nullable|date|before_or_equal:date_demarrage',
            'statut' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
        ], [
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée est invalide.',
            'evenement_id.required' => 'L\'événement est obligatoire.',
            'evenement_id.exists' => 'L\'événement sélectionné est invalide.',
            'nom.required' => 'Le nom du jeu est obligatoire.',
            'date_limite_inscription.before_or_equal' => 'La date limite doit être antérieure ou égale à la date de démarrage.',
        ]);

        // 1. Vérifier les données envoyées
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        // 2. Vérifier que l'événement appartient à l'utilisateur
        $evenement = \App\Models\Evenement::where('id', $request->evenement_id)
            ->where('user_id', $request->user()->id)
            ->where('deleted', false)
            ->first();

        if (!$evenement) {
            return response()->json([
                'message' => 'Cet événement ne vous appartient pas ou n\'existe pas.',
                'status' => 403,
            ], 403);
        }

        // 3. Vérifier que la catégorie appartient à l'utilisateur
        $categorie = Categorie::where('id', $request->categorie_id)
            ->where('user_id', $request->user()->id)
            ->where('deleted', false)
            ->first();

        if (!$categorie) {
            return response()->json([
                'message' => 'Cette catégorie ne vous appartient pas ou n\'existe pas.',
                'status' => 403,
            ], 403);
        }


        $jeu = Jeu::create([
            'user_id' => $request->user()->id,
            'categorie_id' => $request->categorie_id,
            'evenement_id' => $request->evenement_id,
            'nom' => $request->nom,
            'description' => $request->description,
            'image' => $request->image,
            'nombre_joueurs' => $request->nombre_joueurs,
            'nombre_equipes' => $request->nombre_equipes,
            'est_payant' => $request->boolean('est_payant', false),
            'montant_inscription' => $request->boolean('est_payant') ? $request->montant_inscription : 0,
            'mode_competition' => $request->mode_competition,
            'date_demarrage' => $request->date_demarrage,
            'date_limite_inscription' => $request->date_limite_inscription,
            'statut' => $request->statut ?? 'en_attente',
            'active' => $request->boolean('active', true),
            'deleted' => false,
        ]);

        $jeu->load('categorie', 'evenement');

        return response()->json([
            'message' => 'Jeu created successfully',
            'data' => new JeuResource($jeu),
            'status' => 201,
        ], 201);
    }

    /**
     * Mettre à jour un jeu.
     */
    public function update(Request $request, $id)
    {
        $jeu = Jeu::where('user_id', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jeu) {
            return response()->json([
                'message' => 'Jeu not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'categorie_id' => 'sometimes|required|exists:categories,id',
            'evenement_id'=>'sometimes|required|exists:evenements,id',
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'nombre_joueurs' => 'nullable|integer|min:1',
            'nombre_equipes' => 'nullable|integer|min:1',
            'est_payant' => 'nullable|boolean',
            'montant_inscription' => 'nullable|numeric|min:0',
            'mode_competition' => 'nullable|string|max:255',
            'date_demarrage' => 'nullable|date',
            'date_limite_inscription' => 'nullable|date',
            'statut' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        // Vérifier que la catégorie appartient à l'utilisateur
        if ($request->filled('categorie_id')) {
            $categorie = Categorie::where('id', $request->categorie_id)
                ->where('user_id', $request->user()->id)
               ->where('deleted', false)
                ->first();
        
            if (!$categorie) {
                return response()->json([
                    'message' => 'Cette catégorie ne vous appartient pas ou n\'existe pas.',
                    'status' => 403,
                ], 403);
            }
        }

        // Vérifier que l'événement appartient à l'utilisateur
        if ($request->filled('evenement_id')) {
           $evenement = \App\Models\Evenement::where('id', $request->evenement_id)
               ->where('user_id', $request->user()->id)
               ->where('deleted', false)
               ->first();
        
            if (!$evenement) {
                return response()->json([
                    'message' => 'Cet événement ne vous appartient pas ou n\'existe pas.',
                    'status' => 403,
                ], 403);
            }
        }

        $jeu->update($request->only([
            'categorie_id',
            'evenement_id',
            'nom',
            'description',
            'image',
            'nombre_joueurs',
            'nombre_equipes',
            'est_payant',
            'montant_inscription',
            'mode_competition',
            'date_demarrage',
            'date_limite_inscription',
            'statut',
            'active',
        ]));

        $jeu->load('categorie', 'evenement');

        return response()->json([
            'message' => 'Jeu updated successfully',
            'data' => new JeuResource($jeu),
            'status' => 200,
        ], 200);
    }

    /**
     * Suppression logique (soft delete via colonne deleted).
     */
    public function destroy(Request $request, $id)
    {
        $jeu = Jeu::where('user_id', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jeu) {
            return response()->json([
                'message' => 'Jeu not found',
                'status' => 404,
            ], 404);
        }

        $jeu->update([
            'deleted' => true,
            'active' => false,
        ]);

        return response()->json([
            'message' => 'Jeu deleted successfully',
            'status' => 200,
        ], 200);
    }
}
