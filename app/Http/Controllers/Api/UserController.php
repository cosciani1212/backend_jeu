<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs.
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'structure'])
            ->where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('telephone', 'like', "%$search%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher un utilisateur.
     */
    public function show($id)
    {
        $user = User::with(['role', 'structure'])
            ->where('deleted', false)
            ->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => new UserResource($user),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer un utilisateur.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'telephone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'structure_id' => 'nullable|exists:structures,id',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'role_id.required' => 'Le rôle est obligatoire.',
            'role_id.exists' => 'Le rôle sélectionné est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'structure_id' => $request->structure_id,
            'active' => true,
            'deleted' => false,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user->load(['role', 'structure'])),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier un utilisateur.
     */
    public function update(Request $request, $id)
    {
        $user = User::where('deleted', false)->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'telephone' => 'required|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'structure_id' => 'nullable|exists:structures,id',
            'active' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'role_id.exists' => 'Le rôle sélectionné est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'role_id' => $request->role_id,
            'structure_id' => $request->structure_id,
            'active' => $request->active ?? $user->active,
            'password' => $request->filled('password')
                ? Hash::make($request->password)
                : $user->password,
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user->load(['role', 'structure'])),
            'status' => 200,
        ], 200);
    }

    /**
     * Désactiver ou supprimer logiquement un utilisateur.
     */
    public function destroy($id)
    {
        $user = User::where('deleted', false)->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'status' => 404,
            ], 404);
        }

        $user->update([
            'deleted' => true,
            'active' => false,
        ]);

        return response()->json([
            'message' => 'User deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Recherche des utilisateurs.
     */
    public function search(Request $request)
    {
        $query = User::with(['role', 'structure'])
            ->where('deleted', false);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($query->latest()->get()),
            'status' => 200,
        ], 200);
    }
}
