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
        $sql = "select nnrt_id,name as pair_name from <graphs>.node_node_relation_types where deleted=0";
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
        $sql = $sql . " and source_node<>destination_node limit 10"; //same node can't connect with itself";
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
        $sql = $sql . " and source_node<>destination_node limit 10"; //same node can't connect with itself";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'destinationNodeRecords' => $result
        ]);
    }

    public function getMasterLists(Request $request)
    {
        $sql = "with recursive graph_data (sourcenode,destinationnode,level,nnrt_id) as (select distinct source_node,destination_node,1 as label,nnrt_id from 'graphs.node_edge_rels' ndr where 1=1";
        if ($request->source_node != "" && $request->source_node != 'undefined') {
            $sourceNodeImplode = implode(",", $request->source_node);
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
        } else {
            $sql = $sql . " and source_node in (32823,33163)";
        }

        if ($request->destination_node != "" && $request->destination_node != "undefined") {
            $destinationNodeImplode = implode(",", $request->destination_node);
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        } else {
            $sql = $sql . " and destination_node in (45136,25257,46776)";
        }

        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id in (" . $request->nnrt_id . ")"; // pass node-node relation type id
        } else {
            $sql = $sql . " and nnrt_id in (1)";
        }

        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself
        // -- and edge_type_id in (21) -- pass edge_type_id for Level 1
        $sql = $sql . " union all ";
        $sql = $sql . " select distinct ndr.source_node,ndr.destination_node,level+1  as level,ndr.nnrt_id from 'graphs.node_edge_rels' ndr,graph_data gd where gd.destinationnode=ndr.source_node ";
        $sql = $sql . "and ndr.source_node<>ndr.destination_node"; //-- same node can't connect with itself
        if ($request->nnrt_id != "") {
            $sql = $sql . " and ndr.nnrt_id in (" . $request->nnrt_id . ")"; // -- For Level 2 nntr selection (and above)
        } else {
            $sql = $sql . " and ndr.nnrt_id in (1,2)";
        }
        // -- and edge_type_id in (21) -- pass edge_type_id for Level 2 and above
        /*
        and (case when (level+1=2) then ndr.nnrt_id in (2)
        when (level+1=3) then ndr.nnrt_id in (3) else null end
        )
        */
        // -- keep commented for future reference
        $sql = $sql . " and level < 2 )"; //-- upto this level keep as it is
        // -- SEARCH depth FIRST BY sourcenode SET ordercol
        $sql = $sql . " cycle  sourcenode set is_cycle using path,";
        $sql = $sql . " relevant_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nntr_id,edge_type_ids,edge_type_article_type_row) as (select source_node,n1.name as source_node_name,destination_node,n2.name as destination_node_name,level,ner.nnrt_id,array_agg(edge_type_id),array_agg(row(edge_type_id,article_type_id)) edge_type_article_type_row,array_agg(ner.id) as node_edge_rel_ids from 'graphs.node_edge_rels' ner join graph_data gd on gd.sourcenode=ner.source_node and gd.destinationnode=ner.destination_node join 'graphs.nodes' n1 on gd.sourcenode=n1.node_id join 'graphs.nodes' n2 on gd.destinationnode=n2.node_id ";
        // $sql = $sql . " -- where 1=1";
        $sql = $sql . " group by 1,2,3,4,5,6 ) select * from relevant_data rd order by 5";
        // $sql = $sql ." offset 50";
        $sql = $sql . " limit 1000";

        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
        ]);
    }
}