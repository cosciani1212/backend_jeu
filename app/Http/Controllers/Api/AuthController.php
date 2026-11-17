<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Structure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // CONNEXION
    public function login(Request $request)
    {
        // Validation des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Recherche de l'utilisateur
        $user = User::where('email', $request->email)->first();

        // Vérification des identifiants
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects'
            ], 401);
        }

        // Génération du token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }


// INSCRIPTION UTILISATEUR ET CREATION DE STRUCTURE
public function registerStructure(Request $request)
{
    // 1. Validation des données
    $validator = Validator::make($request->all(), [
        // Informations utilisateur
        'name' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'telephone' => 'nullable|string|max:20',
        'password' => 'required|string|min:8|confirmed',

        // Informations structure
        'nom' => 'required|string|max:255',
        'sigle' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'adresse' => 'nullable|string|max:255',
        'telephone_structure' => 'nullable|string|max:20',
        'email_structure' => 'nullable|email|max:255',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $resultat = DB::transaction(function () use ($request) {

            // 2. Création de l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => $request->password,
                'role_id' => null,
                'structure_id' => null,
            ]);

            // 3. Création de la structure
            $structure = Structure::create([
                'user_id' => $user->id,
                'nom' => $request->nom,
                'sigle' => $request->sigle,
                'description' => $request->description,
                'adresse' => $request->adresse,
                'telephone' => $request->telephone_structure,
                'email' => $request->email_structure,
                'active' => true,
                'deleted' => false,
                'inserted_by' => $user->id,
            ]);

            // 4. Association de la structure à l'utilisateur
            $user->update([
                'structure_id' => $structure->id,
            ]);

            // 5. Génération du token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'structure' => $structure,
                'token' => $token,
            ];
        });

        // 6. Réponse JSON
        return response()->json([
            'message' => 'Inscription et création de la structure réussies',
            'user' => $resultat['user'],
            'structure' => $resultat['structure'],
            'token' => $resultat['token'],
            'token_type' => 'Bearer',
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Une erreur est survenue lors de l inscription',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    // DECONNEXION
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    // UTILISATEUR CONNECTÉ
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ], 200);
    }
}

    
