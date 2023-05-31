<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function index()
    // {
    //     //
    // }

    public function _construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    //login function
    // public function login(Request $request){
    //     $email = $request->email;
    //     $password = md5($request->password);
    //     // $sql = "SELECT * FROM users WHERE email='".$email."' AND passwd='".$password."' AND deleted=0";
    //     $sql = "SELECT * FROM users WHERE email='".$email."' AND password='".$password."'";
    //     // echo $sql;
    //     $result = DB::select(DB::raw($sql));
    //     return response()->json([
    //         'search' => $result
    //     ]);
    // }

    //Register function
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $user = User::create(
            array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            )
        );
        return response()->json([
            'message' => 'User Successfully registered',
            'user' => $user
        ], 201);
    }



    //Login section
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()]);
        }

        if (!$token = auth()->setTTL(1440)->attempt($validator->validated())) {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }
        $login = $this->createNewToken($token);
        return response()->json(['success' => true, 'search' => $login]);
    }

    public function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expireTimeUnit' => 'second',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    //Profile section
    public function profile()
    {
        return response()->json(['success' => true, 'data' => auth()->user()]);
    }

    //Get Users Lists section
    public function getUsersLists()
    {
        // try{
        //     $user = auth()->userOrFail();
        // }
        // catch(\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e){
        //     return response()->json(['error' => $e->getMessage()], 401);
        // }        
        $sql = "SELECT * FROM users WHERE deleted=0";
        $result = DB::select(DB::raw($sql));
        return response()->json([
            'usersRecords' => $result
        ]);
    }

    //Change password section
    public function managePassword(Request $request)
    {
        // $old_password = bcrypt($request->old_password);
        $new_password = bcrypt($request->new_password);

        // $sql_update_query = "UPDATE users set password='" . $new_password . "'  WHERE user_id='" . $request->userId['user_id'] . "' AND password='" . $old_password . "'";
        $sql_update_query = "UPDATE users set password='" . $new_password . "' WHERE user_id='" . $request->userId['user_id'] . "'";
        // echo $sql_update_query;
        $result = DB::update(DB::raw($sql_update_query));

        // return back()->with("status", "Password changed successfully!");

        if (is_null($result)) {
            return response()->json(array('failure' => true));
        } else {
            return response()->json(array('success' => true));
        }


        # Validation
        // $request->validate([
        //     'old_password' => 'required',
        //     'new_password' => 'required|confirmed',
        // ]);

        // #Match The Old Password
        // if (!Hash::check($request->old_password, auth()->user()->password)) {
        //     return back()->with("error", "Old Password Doesn't match!");
        // }

        // #Update the new Password
        // User::whereUser_Id(auth()->user()->id)->update([
        //     'password' => Hash::make($request->new_password)
        // ]);

        // return back()->with("status", "Password changed successfully!");
        // return response()->json([
        //     'usersRecords' => $result
        // ]);

    }

    //Logout section
// public function logout(){
//     auth()->logout();
//     return response()->json([
//         'message'=>'User Logged out',
//     ]);
// }
}