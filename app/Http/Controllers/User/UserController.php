<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserCollection;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // "/users?search=jose"
        $search = $request->get("search");
        $users = User::where(DB::raw("CONCAT(users.name,' ',
                                IFNULL(users.surname,''),' ',IFNULL(users.phone,''),' ',
                            users.email,' ',IFNULL(users.n_document,''))"),"like","%".$search."%")
                    ->orderBy("id","desc")->paginate(25);
        $roles = Role::all();
        return response()->json([
            "total" => $users->total(),
            "pagination" => 25,
            "users" => UserCollection::make($users),
            "roles" => $roles->map(function($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $is_exits_user = User::where("email",$request->email)->first();

        if($is_exits_user){
            return response()->json([
                "code" => 405,
                "message" => "El email para este usuario ya existe"  
            ]);
        }

        if($request->hasFile("imagen")){
            $path = Storage::putFile("users",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }

        $user = User::create($request->all());
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return response()->json([
            "user" => UserResource::make($user),
            "code" => 200,
            "message" => "El usuario se creado correctamente"
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
        $is_exits_user = User::where("id","<>",$id)->where("email",$request->email)->first();

        if($is_exits_user){
            return response()->json([
                "code" => 405,
                "message" => "El email para este usuario ya existe"  
            ]);
        }

        $user = User::findOrFail($id);

        if($request->hasFile("imagen")){
            if($user->avatar){
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }

        if($user->role_id != $request->role_id){
            $roleOld = Role::findOrFail($user->role_id);
            $user->removeRole($roleOld);

            $role = Role::findOrFail($request->role_id);
            $user->assignRole($role);
        }

        $user->update($request->all());
        return response()->json([
            "user" => UserResource::make($user),
            "code" => 200,
            "message" => "El usuario se editado correctamente"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if($user->avatar){
            Storage::delete($user->avatar);
        }
        $user->delete();

        return response()->json([
            "code" => 200,
            "message" => "El usuario se eliminado correctamente"
        ]);
    }
}