<?php

namespace App\Http\Controllers\Role;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // list?search=admin
        $search = $request->get("search");

        $roles = Role::where("name","like","%".$search."%")->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total" => $roles->total(),
            "pagination" => 25,
            "roles" => $roles->map(function($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name,
                    "permissions" => $role->permissions->pluck("name"),
                    "created_at" => $role->created_at->format("Y-m-d h:i A"),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exist_role = Role::where("name",$request->name)->first();
        if($exist_role){
            return response()->json([
                "code" => 405,
                "message" => "El nombre de este rol ya existe"  
            ]);
        }

        $role = Role::create([
           "name" => $request->name,
           "guard_name" => "api"
        ]);

        // LISTA DE PERMISOS ["register_role","register_user","register_categorie"]

        $permissions = $request->permissions;
        foreach ($permissions as $key => $permission) {
            $role->givePermissionTo($permission);
        }

        return response()->json([
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions->pluck("name"),
                "created_at" => $role->created_at->format("Y-m-d h:i A"),
            ],
            "code" => 200,
            "message" => "El rol se ha registrado correctamente"  
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Admin sede
        // Asesor -> Admin sede
        // Almacen
        $exist_role = Role::where("id","<>",$id)->where("name",$request->name)->first();
        if($exist_role){
            return response()->json([
                "code" => 405,
                "message" => "El nombre de este rol ya existe"  
            ]);
        }

        $role = Role::findOrFail($id);
        
        $role->update([
           "name" => $request->name,
        ]);

        // LISTA DE PERMISOS ["register_role","register_user","register_categorie"]
        $permissions = $request->permissions;
        $role->syncPermissions($permissions);
        return response()->json([
            "role" => [
                "id" => $role->id,
                "name" => $role->name,
                "permissions" => $role->permissions->pluck("name"),
                "created_at" => $role->created_at->format("Y-m-d h:i A"),
            ],
            "code" => 200,
            "message" => "El rol se ha editado correctamente"  
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            "message" => "El rol se elimino correctamente"
        ]);
    }
}
