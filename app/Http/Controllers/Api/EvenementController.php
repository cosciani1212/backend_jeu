<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\EvenementResource;
use App\Models\Evenement;
use Illuminate\Support\Facades\Validator;

class EvenementController extends Controller
{
    /**
     * Liste des événements de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Evenement::with(['structure', 'jeus'])
            ->where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('titre', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $evenements = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Événements récupérés avec succès',
            'data' => EvenementResource::collection($evenements),
            'meta' => [
                'current_page' => $evenements->currentPage(),
                'last_page' => $evenements->lastPage(),
                'total' => $evenements->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher un événement.
     */
    public function show(Request $request, $id)
    {
        $evenement = Evenement::with(['structure', 'jeus'])
            ->where('inserted_by', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$evenement) {
            return response()->json([
                'message' => 'Événement introuvable',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Événement récupéré avec succès',
            'data' => new EvenementResource($evenement),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer un événement.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'structure_id' => 'required|exists:structures,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lieu' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'nullable|in:en_attente,publie,termine,annule',
            'active' => 'nullable|boolean',
        ], [
            'structure_id.required' => 'La structure est obligatoire.',
            'structure_id.exists' => 'La structure sélectionnée est invalide.',
            'titre.required' => 'Le titre est obligatoire.',
            'lieu.required' => 'Le lieu est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'active.boolean' => 'Le champ actif doit être un booléen.',
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            if ($request->filled('structure_id')) {
                $structureExiste = $user->structures()
                    ->where('id', $request->structure_id)
                    ->where('deleted', false)
                    ->exists();

                if (!$structureExiste) {
                    $validator->errors()->add(
                        'structure_id',
                        'Cette structure ne vous appartient pas.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $evenement = Evenement::create([
            'user_id' => $user->id,
            'structure_id' => $request->structure_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'statut' => $request->statut ?? 'en_attente',
            'active' => $request->active ?? true,
            'deleted' => false,
            'inserted_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Événement créé avec succès',
            'data' => new EvenementResource(
                $evenement->load(['structure', 'jeus'])
            ),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier un événement.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $evenement = Evenement::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$evenement) {
            return response()->json([
                'message' => 'Événement introuvable',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'structure_id' => 'required|exists:structures,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lieu' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'statut' => 'nullable|in:en_attente,publie,termine,annule',
            'active' => 'nullable|boolean',
        ], [
            'structure_id.required' => 'La structure est obligatoire.',
            'structure_id.exists' => 'La structure sélectionnée est invalide.',
            'titre.required' => 'Le titre est obligatoire.',
            'lieu.required' => 'Le lieu est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'active.boolean' => 'Le champ actif doit être un booléen.',
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            if ($request->filled('structure_id')) {
                $exists = $user->structures()
                    ->where('id', $request->structure_id)
                    ->where('deleted', false)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'structure_id',
                        'Cette structure ne vous appartient pas.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $evenement->update([
            'structure_id' => $request->structure_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'lieu' => $request->lieu,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'statut' => $request->statut ?? $evenement->statut,
            'active' => $request->active ?? $evenement->active,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Événement mis à jour avec succès',
            'data' => new EvenementResource(
                $evenement->load(['structure', 'jeus'])
            ),
            'status' => 200,
        ], 200);
    }

    /**
     * Supprimer logiquement un événement.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $evenement = Evenement::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$evenement) {
            return response()->json([
                'message' => 'Événement introuvable',
                'status' => 404,
            ], 404);
        }

        $evenement->update([
            'deleted' => true,
            'active' => false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Événement supprimé avec succès',
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche des événements.
     */
    public function search(Request $request)
    {
        $user = $request->user();

        $query = Evenement::with(['structure', 'jeus'])
            ->where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $query->where('titre', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Événements récupérés avec succès',
            'data' => EvenementResource::collection($query->latest()->get()),
            'status' => 200,
        ], 200);
    }
}