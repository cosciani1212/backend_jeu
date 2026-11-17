<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\JuryResource;
use App\Models\Jury;
use Illuminate\Support\Facades\Validator;

class JuryController extends Controller
{
    /**
     * Liste des jurys de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Jury::with('evenement')
            ->where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('contact', 'like', "%$search%");
            });
        }

        $jurys = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Juries retrieved successfully',
            'data' => JuryResource::collection($jurys),
            'meta' => [
                'current_page' => $jurys->currentPage(),
                'last_page' => $jurys->lastPage(),
                'total' => $jurys->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher un jury.
     */
    public function show(Request $request, $id)
    {
        $jury = Jury::with('evenement')
            ->where('inserted_by', $request->user()->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jury) {
            return response()->json([
                'message' => 'Jury not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Jury retrieved successfully',
            'data' => new JuryResource($jury),
            'status' => 200,
        ], 200);
    }

    /**
     * Ajouter un jury à un événement.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'evenement_id' => 'required|exists:evenements,id',
            'nom' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'active' => 'nullable|boolean',
        ], [
            'evenement_id.required' => 'L\'événement est obligatoire.',
            'evenement_id.exists' => 'L\'événement sélectionné est invalide.',
            'nom.required' => 'Le nom du jury est obligatoire.',
            'contact.required' => 'Le contact du jury est obligatoire.',
            'contact.max' => 'Le contact ne peut pas dépasser 255 caractères.',
            'active.boolean' => 'L\'état actif doit être un booléen.',
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            if ($request->filled('evenement_id')) {
                $evenementExiste = $user->evenements()
                    ->where('id', $request->evenement_id)
                    ->where('deleted', false)
                    ->exists();

                if (!$evenementExiste) {
                    $validator->errors()->add(
                        'evenement_id',
                        'Cet événement ne vous appartient pas.'
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

        $jury = Jury::create([
            'evenement_id' => $request->evenement_id,
            'nom' => $request->nom,
            'contact' => $request->contact,
            'active' => $request->active ?? true,
            'deleted' => false,
            'inserted_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Jury created successfully',
            'data' => new JuryResource($jury->load('evenement')),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier un jury.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $jury = Jury::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jury) {
            return response()->json([
                'message' => 'Jury not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'evenement_id' => 'required|exists:evenements,id',
            'nom' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'active' => 'nullable|boolean',
        ], [
            'evenement_id.required' => 'L\'événement est obligatoire.',
            'evenement_id.exists' => 'L\'événement sélectionné est invalide.',
            'nom.required' => 'Le nom du jury est obligatoire.',
            'contact.required' => 'Le contact du jury est obligatoire.',
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            if ($request->filled('evenement_id')) {
                $exists = $user->evenements()
                    ->where('id', $request->evenement_id)
                    ->where('deleted', false)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'evenement_id',
                        'Cet événement ne vous appartient pas.'
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

        $jury->update([
            'evenement_id' => $request->evenement_id,
            'nom' => $request->nom,
            'contact' => $request->contact,
            'active' => $request->active ?? $jury->active,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Jury updated successfully',
            'data' => new JuryResource($jury->load('evenement')),
            'status' => 200,
        ], 200);
    }

    /**
     * Supprimer logiquement un jury.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $jury = Jury::where('inserted_by', $user->id)
            ->where('deleted', false)
            ->find($id);

        if (!$jury) {
            return response()->json([
                'message' => 'Jury not found',
                'status' => 404,
            ], 404);
        }

        $jury->update([
            'deleted' => true,
            'active' => false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Jury deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Rechercher des jurys.
     */
    public function search(Request $request)
    {
        $user = $request->user();

        $query = Jury::with('evenement')
            ->where('inserted_by', $user->id)
            ->where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('contact', 'like', "%$search%");
            });
        }

        return response()->json([
            'message' => 'Juries retrieved successfully',
            'data' => JuryResource::collection($query->latest()->get()),
            'status' => 200,
        ], 200);
    }
}


