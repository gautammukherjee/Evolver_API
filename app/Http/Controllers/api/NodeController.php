<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class NodeController extends Controller
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
    // public function getNodeSelects()
    // {
    //     $sql = "SELECT ta_id, name, description, created_at FROM testing.tas WHERE deleted=0";
    //     $result = DB::select($sql);
    //     return response()->json([
    //         'nodeSelectsRecords' => $result
    //     ]);
    // }

    public function getNodeSelects()
    {
        $sql = "select nnrt_id,name as pair_name from graphs.node_node_relation_types where deleted=0";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);
    }

    public function getEdgeType()
    {
        $sql = "select edge_type_id,name as edge_type_name from graphs.edge_types where deleted=0";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'edgeTypeRecords' => $result
        ]);
    }

    public function getSourceNode(Request $request)
    {
        $sql = "select distinct ndr.source_node,n1.name as source_node_name from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id"; //join graphs.nodes n2 on ndr.destination_node=n2.node_id
        $sql = $sql . " where 1=1";
        //$sql .= "-- and source_node in (11499,18153)";
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id in (" . $request->nnrt_id . ")"; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node limit 100"; //same node can't connect with itself";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'sourceNodeRecords' => $result
        ]);
    }

    public function getDestinationNode(Request $request)
    {
        $sql = "select distinct destination_node,n2.name as destination_node_name from graphs.node_edge_rels ndr join graphs.nodes n2 on ndr.destination_node=n2.node_id";
        $sql = $sql . " where 1=1";
        //$sql .= "-- and source_node in (11499,18153)";
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id in (" . $request->nnrt_id . ")"; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node limit 100"; //same node can't connect with itself";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'destinationNodeRecords' => $result
        ]);
    }
}