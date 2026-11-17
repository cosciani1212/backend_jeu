<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Liste de tous les rôles.
     */
    public function index(Request $request)
    {
        $query = Role::query();

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        $roles = $query->orderBy('nom', 'asc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => RoleResource::collection($roles),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'total' => $roles->total(),
            ],
            'status' => 200,
        ], 200);
    }

    /**
     * Afficher un rôle.
     */
    public function show($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Role retrieved successfully',
            'data' => new RoleResource($role),
            'status' => 200,
        ], 200);
    }

    /**
     * Créer un rôle.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:roles,nom',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:255',
        ], [
            'nom.required' => 'Le nom du rôle est obligatoire.',
            'nom.unique' => 'Ce rôle existe déjà.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'permissions.array' => 'Les permissions doivent être un tableau.',
            'permissions.*.string' => 'Chaque permission doit être une chaîne de caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $role = Role::create([
            'nom' => $request->nom,
            'permissions' => $request->permissions ?? [],
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => new RoleResource($role),
            'status' => 201,
        ], 201);
    }

    /**
     * Modifier un rôle.
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:roles,nom,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:255',
        ], [
            'nom.required' => 'Le nom du rôle est obligatoire.',
            'nom.unique' => 'Ce nom de rôle est déjà utilisé.',
            'permissions.array' => 'Les permissions doivent être un tableau.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les données envoyées ne sont pas valides.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $role->update([
            'nom' => $request->nom,
            'permissions' => $request->permissions ?? $role->permissions,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role),
            'status' => 200,
        ], 200);
    }

    /**
     * Supprimer un rôle.
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status' => 404,
            ], 404);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce rôle car il est attribué à des utilisateurs.',
                'status' => 409,
            ], 409);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Rechercher des rôles.
     */
    public function search(Request $request)
    {
        $query = Role::query();

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => RoleResource::collection($query->get()),
            'status' => 200,
        ], 200);
    }
}

