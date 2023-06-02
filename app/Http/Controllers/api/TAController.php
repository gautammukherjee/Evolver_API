<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TAController extends Controller
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

    //Get TA Lists section
    public function getTasLists()
    {
        $sql = "SELECT ta_id, name, description, created_at FROM testing.tas WHERE deleted=0";
        $result = DB::select($sql);
        return response()->json([
            'tasRecords' => $result
        ]);
    }
}