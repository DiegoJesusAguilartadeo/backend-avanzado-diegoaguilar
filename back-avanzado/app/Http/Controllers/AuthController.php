<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Tymon\JWTAuth\Facades\JWTAuth;

 

class AuthController extends Controller
{
    public function register(Request $request){
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email'  => 'required|email|unique:users',
            'password'  => 'required|min:8|confirmed',

        ]);

        $user = User::create([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), 
            'role'  => 'user',
        ]);

        return response()->json(['user' => $user], 201);
    }

        #[OA\Post(
        path: "/api/login",
        summary: "Autenticar usuario y obtener token JWT",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "ana@correo.com"),
                    new OA\Property(property: "password", type: "string", example: "secreta123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login exitoso",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "access_token", type: "string")]
                )
            ),
            new OA\Response(response: 401, description: "Credenciales inválidas")
        ]
    )]
    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

                                        //cliente            //DB
        if(! $user || ! Hash::check($request->password, $user->password)){
            return response()->json(['message' => 'Credenciales invalidas'], 401);
        }

        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)){
            return response()->json(['message' => 'No Autorizado'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() *60,
        ]);

        //return response()->json(['message' => 'Login exitoso', 'user' => $user], 200);
    }

    /*public function me(Request $request){
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        return response()->json($user);
    }*/

    public function me(){
        return response()->json(auth('api')->user());
    }

    public function meByEmail(Request $request){
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        return response()->json($user);
    }

    public function logout() {
        auth('api')->logout();
        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }

}
