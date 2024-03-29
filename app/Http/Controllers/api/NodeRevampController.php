<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class NodeRevampController extends Controller
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

    public function getNodeSelects2(Request $request)
    {
        $sql = "select nnrt_id,name as pair_name from graphs.node_node_relation_types where deleted=0";
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id != " . $request->nnrt_id; // pass node-node relation type id
        }
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);
    }

    public function getEdgeTypeFirst() // First we intialize the edge type first then merge with edge group table
    {
        $sql = "select edge_type_id,name as edge_type_name, edge_group_id from graphs.edge_types where deleted=0";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'edgeTypeFirstRecords' => $result
        ]);
    }
    public function getEdgeType()
    {
        // $sql = "select e.edge_type_id, e.name as edge_type_name, eg.edge_group_id, eg.name as edge_group_name
        // from graphs.edge_types as e join graphs.edge_type_group_master as eg 
        // on e.edge_group_id=eg.edge_group_id
        // where e.deleted=0";
        $sql = "select edge_group_id, name as edge_group_name from graphs.edge_type_group_master";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'edgeTypeRecords' => $result
        ]);
    }

    public function getEdgeTypeName(Request $request)
    {
        // $sql = "select name as edge_type_name from graphs.edge_types ";
        $sql = "select e.edge_type_id, eg.name as edge_type_name from graphs.edge_types as e join graphs.edge_type_group_master as eg on e.edge_group_id=eg.edge_group_id ";
        $edge_type_ids = collect($request->edge_type_ids);
        $edge_type_idsImplode = $edge_type_ids->implode(', ');
        if (!empty($edge_type_idsImplode))
            $sql = $sql . " where e.edge_type_id in (" . $edge_type_idsImplode . ")"; // pass node-node relation type id

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'edgeTypeName' => $result
        ]);
    }

    public function getSourceNode(Request $request)
    {
        $sql = "select distinct ns1.node_syn_id, ns1.name as syn_node_name,ndr.source_node,n1.name as source_node_name from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id"; //join graphs.nodes n2 on ndr.destination_node=n2.node_id
        $sql = $sql . " join graphs.node_syns ns1 on n1.node_id=ns1.node_id "; // -- (Uncomment when source_node_synonym name searched)

        $sql = $sql . " where 1=1";
        // $sql = $sql . " and source_node in (11499,18153)";
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node"; //same node can't connect with itself";
        if ($request->searchval != "") {
            $sql = $sql . " and (n1.name ilike '$request->searchval%' OR ns1.name ilike '$request->searchval%')"; // search with source node
            // $sql = $sql . " and ns1.name ilike '%$request->searchval%' "; // search with synonym source node
        }
        $sql = $sql . "order by source_node_name";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'sourceNodeRecords' => $result
        ]);
    }

    public function getSourceNode2(Request $request)
    {
        $sql = "select distinct ndr.source_node,n1.name as source_node_name from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id join graphs.nodes n2 on ndr.destination_node=n2.node_id where 1=1 and source_node in ";
        $sql = $sql . " (select distinct destination_node from graphs.node_edge_rels ndr where 1=1 ";

        //1. Source Node 1
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        // echo "heree2: " . $sourceNodeImplode;
        if (!empty($sourceNodeImplode))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . ")"; // pass node-node relation type id


        //2. Destination Node 1
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            // echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode))
                $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        }

        //3. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node "; //same node can't connect with itself";
        $sql = $sql . " ) ";

        if ($request->nnrt_id2 != "" && $request->nnrt_id2 != "undefined") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node ";

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'sourceNodeRecords2' => $result
        ]);
    }

    public function getDestinationNode(Request $request)
    {
        $sql = "select distinct ns2.node_syn_id, ns2.name as syn_node_name, destination_node,n2.name as destination_node_name from graphs.node_edge_rels ndr join graphs.nodes n2 on ndr.destination_node=n2.node_id ";
        $sql = $sql . " left join graphs.node_syns ns2 on n2.node_id=ns2.node_id"; //(Uncomment when destination_node_synonym name searched)";

        $sql = $sql . " where 1=1";

        //1. Source Node 1
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        if (!empty($sourceNodeImplode))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . ")"; // pass node-node relation type id

        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node "; //same node can't connect with itself";
        if ($request->searchval != "") {
            $sql = $sql . " and (n2.name ilike '$request->searchval%' OR ns2.name ilike '$request->searchval%')"; //serach with destination node
            // $sql = $sql . " and ns2.name ilike '%$request->searchval%' "; // search with synonym destination node
        }

        //3. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        $sql = $sql . "order by destination_node_name";

        if ($request->offSetValue != "") {
            $sql = $sql . " offset " . $request->offSetValue;
        }

        if ($request->limitValue != "") {
            $sql = $sql . "limit " . $request->limitValue;
        }
        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'destinationNodeRecords' => $result
        ]);
    }

    public function getDestinationNode2(Request $request)
    {
        $sql = "select distinct ns2.node_syn_id, ns2.name as syn_node_name, destination_node,n2.name as destination_node_name from graphs.node_edge_rels ndr join graphs.nodes n2 on ndr.destination_node=n2.node_id ";
        $sql = $sql . " join graphs.node_syns ns2 on n2.node_id=ns2.node_id"; //(Uncomment when destination_node_synonym name searched)";

        $sql = $sql . " where 1=1";

        //1. Source Node 1
        $sourceNode2 = collect($request->source_node2);
        $sourceNodeImplode2 = $sourceNode2->implode(', ');
        // echo "heree2: " . $sourceNodeImplode;
        if (!empty($sourceNodeImplode2))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode2 . ")"; // pass node-node relation type id

        if ($request->nnrt_id2 != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node "; //same node can't connect with itself";
        if ($request->searchval != "") {
            $sql = $sql . " and (n2.name ilike '$request->searchval%' OR ns2.name ilike '$request->searchval%')"; //serach with destination node
            // $sql = $sql . " and ns2.name ilike '%$request->searchval%' "; // search with synonym destination node
        }
        $sql = $sql . "order by destination_node_name";
        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'destinationNodeRecords2' => $result
        ]);
    }

    public function getMasterListsRevampLevelOne(Request $request)
    {
        //First First level
        $sql = "with  graph_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nnrt_id,edge_type_ids,ne_ids,pmids)
        as (select source_node,n1.name,destination_node,n2.name,1 as label,"; //change 1 with 2 for level 2 and 3 for level 3 like this 
        $sql = $sql . " nnrt_id,array_agg(distinct edge_type_id),array_agg(distinct ndr.id),count(distinct pmid) from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id join graphs.nodes n2 on ndr.destination_node=n2.node_id join lateral (select neslr.pmid from graphs.node_edge_sci_lit_rels neslr where neslr.ne_id=ndr.id) as a on true where 1=1 ";

        //1. Source Node
        $sourceNodeId = '';
        if (!empty($request->node_id)) {
            $sourceNodeId = ", " . $request->node_id;
        }
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        if (!empty($sourceNodeImplode))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . $sourceNodeId . ")"; // pass node-node relation type id
        
        //2. Destination Node
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');            
            if (!empty($destinationNodeImplode))
                $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        }

        //3. Node select
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself

        //4. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        $sql = $sql . " group by 1,2,3,4,5,6) select * from graph_data";

        if ($request->offSetValue != "") {
            $sql = $sql . " offset " . $request->offSetValue;
        }
        if ($request->limitValue != "") {
            $sql = $sql . "limit " . $request->limitValue;
        }
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
        ]);
    }

    public function getMasterListsRevampLevelOneCount(Request $request)
    {
        //First First level
        $sql = "with  graph_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nnrt_id,edge_type_ids,ne_ids,pmids)
        as (select source_node,n1.name,destination_node,n2.name,1 as label,"; //change 1 with 2 for level 2 and 3 for level 3 like this 
        $sql = $sql . " nnrt_id,array_agg(distinct edge_type_id),array_agg(distinct ndr.id),count(distinct pmid) from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id join graphs.nodes n2 on ndr.destination_node=n2.node_id join lateral (select neslr.pmid from graphs.node_edge_sci_lit_rels neslr where neslr.ne_id=ndr.id) as a on true where 1=1 ";

        //1. Source Node
        $sourceNodeId = '';
        if (!empty($request->node_id)) {
            $sourceNodeId = ", " . $request->node_id;
        }
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        if (!empty($sourceNodeImplode))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . $sourceNodeId . ")"; // pass node-node relation type id
        
        //2. Destination Node
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');            
            if (!empty($destinationNodeImplode))
                $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        }

        //3. Node select
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself

        //4. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        $sql = $sql . " group by 1,2,3,4,5,6) select count(*) as count from graph_data";        
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
        ]);
    }

    public function getMasterListsRevampLevelTwo(Request $request)
    {
        // For second level         
        $sql = "with  graph_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nnrt_id,edge_type_ids,ne_ids,pmids)
        as (select source_node,n1.name,destination_node,n2.name,2 as label,"; //change 1 with 2 for level 2 and 3 for level 3 like this 
        $sql = $sql . " nnrt_id,array_agg(distinct edge_type_id),array_agg(distinct ndr.id),count(distinct pmid) from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id join graphs.nodes n2 on ndr.destination_node=n2.node_id join lateral (select neslr.pmid from graphs.node_edge_sci_lit_rels neslr where neslr.ne_id=ndr.id) as a on true where 1=1 ";

        //1. Source Node
        $sourceNodeId = '';
        if (!empty($request->node_id)) {
            $sourceNodeId = ", " . $request->node_id;
        }

        $sourceNode2 = collect($request->source_node2);
        $sourceNodeImplode2 = $sourceNode2->implode(', ');
        if (!empty($sourceNodeImplode2))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode2 . $sourceNodeId . ")"; // pass node-node relation type id
        
        $destinationNode2 = collect($request->destination_node2);
        $destinationNodeImplode2 = $destinationNode2->implode(', ');
        if (!empty($destinationNodeImplode2))
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode2 . ")"; // pass destination node level 2        

        //3. Node select level 2
        if ($request->nnrt_id2 != "") {
            $sql = $sql . " and ndr.nnrt_id = " . $request->nnrt_id2; // -- For Level 2 nntr selection (and above)
        }

        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself

        //4. Edge level 2
        $edgeType2 = collect($request->edge_type_id2);
        $edgeType2Implode = $edgeType2->implode(', ');
        if (!empty($edgeType2Implode))
            $sql = $sql . " and edge_type_id in (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 2 and above        

        $sql = $sql . " group by 1,2,3,4,5,6) select * from graph_data";      

        if ($request->offSetValue != "") {
            $sql = $sql . " offset " . $request->offSetValue;
        }
        if ($request->limitValue != "") {
            $sql = $sql . "limit " . $request->limitValue;
        }
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
        ]);
    }

    public function getMasterListsRevampLevelTwoCount(Request $request)
    {
        // For second level         
        $sql = "with  graph_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nnrt_id,edge_type_ids,ne_ids,pmids)
        as (select source_node,n1.name,destination_node,n2.name,2 as label,"; //change 1 with 2 for level 2 and 3 for level 3 like this 
        $sql = $sql . " nnrt_id,array_agg(distinct edge_type_id),array_agg(distinct ndr.id),count(distinct pmid) from graphs.node_edge_rels ndr join graphs.nodes n1 on ndr.source_node=n1.node_id join graphs.nodes n2 on ndr.destination_node=n2.node_id join lateral (select neslr.pmid from graphs.node_edge_sci_lit_rels neslr where neslr.ne_id=ndr.id) as a on true where 1=1 ";

        //1. Source Node
        $sourceNodeId = '';
        if (!empty($request->node_id)) {
            $sourceNodeId = ", " . $request->node_id;
        }

        $sourceNode2 = collect($request->source_node2);
        $sourceNodeImplode2 = $sourceNode2->implode(', ');
        if (!empty($sourceNodeImplode2))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode2 . $sourceNodeId . ")"; // pass node-node relation type id
        
        $destinationNode2 = collect($request->destination_node2);
        $destinationNodeImplode2 = $destinationNode2->implode(', ');
        if (!empty($destinationNodeImplode2))
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode2 . ")"; // pass destination node level 2        

        //3. Node select level 2
        if ($request->nnrt_id2 != "") {
            $sql = $sql . " and ndr.nnrt_id = " . $request->nnrt_id2; // -- For Level 2 nntr selection (and above)
        }

        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself

        //4. Edge level 2
        $edgeType2 = collect($request->edge_type_id2);
        $edgeType2Implode = $edgeType2->implode(', ');
        if (!empty($edgeType2Implode))
            $sql = $sql . " and edge_type_id in (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 2 and above        

        $sql = $sql . " group by 1,2,3,4,5,6) select count(*) as count from graph_data";      
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
        ]);
    }

    public function getAllRecords(Request $request)
    {
        $sql = "with recursive graph_data (sourcenode,destinationnode,level,nnrt_id) as (select distinct source_node,destination_node,1 as label,nnrt_id from graphs.node_edge_rels ndr where 1=1";

        // if (!empty($request->node_id)) {
        //     //1. Node ID
        //     // echo "heree1: " . $request->node_id;
        //     $sql = $sql . " and source_node = " . $request->node_id;
        // } else {

        //1. Source Node level 1
        $sourceNodeId = '';
        if (!empty($request->node_id)) {
            $sourceNodeId = ", " . $request->node_id;
        }

        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        // echo "heree2: " . $sourceNodeImplode;
        if (!empty($sourceNodeImplode))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode . $sourceNodeId . ")"; // pass node-node relation type id
        // }

        //2. Destination Node level 1
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            // echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode))
                $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        }

        //3. Node select level 1
        if ($request->nnrt_id != "") {
            $sql = $sql . " and nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }

        $sql = $sql . " and source_node<>destination_node"; //-- same node can't connect with itself

        //4. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        $sql = $sql . " union all ";
        $sql = $sql . " select distinct ndr.source_node,ndr.destination_node,level+1  as level,ndr.nnrt_id from graphs.node_edge_rels ndr,graph_data gd where gd.destinationnode=ndr.source_node ";
        $sql = $sql . "and ndr.source_node<>ndr.destination_node"; //-- same node can't connect with itself

        ///////////////////////// FOR LEVEL 2 START HERE ////////////////////////////////

        //1. Source Node level 2
        // $sourceNodeId = '';
        // if (!empty($request->node_id)) {
        //     $sourceNodeId = ", " . $request->node_id;
        // }

        $sourceNode2 = collect($request->source_node2);
        $sourceNodeImplode2 = $sourceNode2->implode(', ');
        if (!empty($sourceNodeImplode2))
            $sql = $sql . " and source_node in (" . $sourceNodeImplode2 . ")"; // pass source node level 2 

        //2. Destination Node level2
        $destinationNode2 = collect($request->destination_node2);
        $destinationNodeImplode2 = $destinationNode2->implode(', ');
        if (!empty($destinationNodeImplode2))
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode2 . ")"; // pass destination node level 2        

        //3. Node select level 2
        if ($request->nnrt_id2 != "") {
            $sql = $sql . " and ndr.nnrt_id = " . $request->nnrt_id2; // -- For Level 2 nntr selection (and above)
        }

        //4. Edge level 2
        $edgeType2 = collect($request->edge_type_id2);
        $edgeType2Implode = $edgeType2->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeType2Implode))
            $sql = $sql . " and edge_type_id in (" . $edgeType2Implode . ")"; //pass edge_type_id for Level 2 and above

        /*
        and (case when (level+1=2) then ndr.nnrt_id in (2)
        when (level+1=3) then ndr.nnrt_id in (3) else null end
        )
        */
        // -- keep commented for future reference

        //7. level select 1 or 2
        if ($request->nnrt_id2 == "") {
            $sql = $sql . " and level < 1 )"; //-- upto this level keep as it is
        } else {
            $sql = $sql . " and level < 2 )"; //-- For 2 level upto this level keep as it is
        }

        // -- SEARCH depth FIRST BY sourcenode SET ordercol
        $sql = $sql . " cycle  sourcenode set is_cycle using path,";
        $sql = $sql . " relevant_data (sourcenode,sourcenode_name,destinationnode,destinationnode_name,level,nntr_id,edge_type_ids,edge_type_article_type_ne_ids,ne_ids,path) as (
        select source_node,n1.name as source_node_name,destination_node,n2.name as destination_node_name,level,ner.nnrt_id,array_agg(edge_type_id),array_agg(row(edge_type_id,article_type_id,ner.id)) edge_type_article_type_ne_id,
        array_agg(distinct ner.id),path from graphs.node_edge_rels ner join graph_data gd on gd.sourcenode=ner.source_node and gd.destinationnode=ner.destination_node and ner.nnrt_id=gd.nnrt_id join graphs.nodes n1 on gd.sourcenode=n1.node_id join graphs.nodes n2 on gd.destinationnode=n2.node_id ";
        // $sql = $sql . " -- where 1=1";
        $sql = $sql . " group by 1,2,3,4,5,6,10 ) select count(1) as total from relevant_data rd";

        $result = DB::select($sql);
        return response()->json([
            'masterListsDataTotal' => $result
        ]);
    }

    public function getDistributionRelationType(Request $request)
    {
        $sql = "SELECT source.edge_type_id, source.Edge_Types_Name AS Edge_Types_Name, source.source_node_id, 
        source.Source_Node_Name, source.destination_node_id,source.Destination_Node_Name, COUNT(*) 
        as count FROM 
        (SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, neslr.pmid 
        AS Node_Edge_Sci_Lit_Rels_pmid,nnrtn.name AS Node_Node_Relation_Types, nnrtn.nnrt_id,nsn.name AS Source_Node_Name,nsn.node_id 
        as source_node_id,ndn.name AS Destination_Node_Name,ndn.node_id as destination_node_id,et.name AS Edge_Types_Name,et.edge_type_id, 
        tet.edge_group_id,tet.name AS Grouped_Edge_Types_Name FROM source.sci_lits as sl INNER JOIN graphs.node_edge_sci_lit_rels AS neslr 
        ON sl.pmid = neslr.pmid JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id JOIN graphs.node_node_relation_types AS nnrtn 
        ON nern.nnrt_id = nnrtn.nnrt_id JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id JOIN graphs.node_edge_rels 
        AS ner ON nern.id = ner.id JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id 
        -- JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id 
        -- LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id 
        JOIN graphs.edge_types et on et.edge_type_id=nern.edge_type_id 
        JOIN graphs.edge_type_group_master tet on tet.edge_group_id=et.edge_group_id where ";
        // sl.publication_date > '2017-06-01' AND
        $sql = $sql . " nsn.node_id <> ndn.node_id ";
        // $sql = $sql . " AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')";

        if($request->nnrt_id2 == ""){
            //For First Level Data Show
            //1. Node select level 1
            if ($request->nnrt_id != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
            }
            //2. Source Node
            $sourceNode = collect($request->source_node);
            $sourceNodeImplode = $sourceNode->implode(', ');       
            if (!empty($sourceNodeImplode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
            
            //3. Destination Node
            if($request->destination_node_all != 1){
                $destinationNode = collect($request->destination_node);
                $destinationNodeImplode = $destinationNode->implode(', ');
                // echo "heree2: " . $destinationNodeImplode;
                if (!empty($destinationNodeImplode))
                    $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            }
            // $destinationNode = collect($request->destination_node);
            // $destinationNodeImplode = $destinationNode->implode(', ');       
            // if (!empty($destinationNodeImplode))
            //     $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
            
            //4. Edge Type
            $edgeType = collect($request->edge_type_id);
            $edgeTypeImplode = $edgeType->implode(', ');
            if (!empty($edgeTypeImplode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id
        }
        else
        {
            //For Second Level Data Show
            //1. Node select level 2
            if ($request->nnrt_id2 != "") {
                $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id2; // pass node-node relation type id
            }
            //2. Source Node2
            $sourceNode2 = collect($request->source_node2);
            $sourceNode2Implode = $sourceNode2->implode(', ');       
            if (!empty($sourceNode2Implode))
                $sql = $sql . " AND nsn.node_id in (" . $sourceNode2Implode . ")"; // pass node-node relation type id        

            //3. Destination Node2
            $destinationNode2 = collect($request->destination_node2);
            $destinationNode2Implode = $destinationNode2->implode(', ');       
            if (!empty($destinationNode2Implode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNode2Implode . ")"; // pass node-node relation type id
            //4. Edge Type2
            $edgeType2 = collect($request->edge_type_id2);
            $edgeType2Implode = $edgeType2->implode(', ');
            if (!empty($edgeType2Implode))
                $sql = $sql . " AND nern.edge_type_id IN (" . $edgeType2Implode . ")"; //pass edge_type_id
        }

        $sql = $sql . " ) AS source GROUP BY 1,2,3,4,5,6 ORDER BY count desc, source.destination_node_name ASC ";
        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'distributionData' => $result
        ]);
    }

    public function getEdgePMIDLists(Request $request)
    {
        $sql = "select distinct neslr.pmid,ner.edge_type_id,";
        $sql = $sql . " (select name from graphs.edge_types WHERE edge_type_id=ner.edge_type_id) as edge_type_name,sl.title,sl.publication_date from graphs.node_edge_sci_lit_rels neslr JOIN graphs.node_edge_rels ner ON neslr.ne_id=ner.id join source.sci_lits sl on neslr.pmid=sl.pmid "; //-- uncomment for additional pmid specific details along with join part

        $ne_ids = collect($request->ne_ids);
        $ne_idsImplode = $ne_ids->implode(', ');
        if (!empty($ne_idsImplode))
            $sql = $sql . " WHERE neslr.ne_id in (" . $ne_idsImplode . ") "; // pass node-node relation type id

        $sql = $sql . "  order by sl.publication_date DESC";

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'pmidLists' => $result
        ]);
    }

    public function getEdgeTypeSentencePMIDLists(Request $request)
    {
        $sql = "select distinct neslr.pmid,
        neslr.ne_id,
        sl.title,
        sl.publication_date,
        ner.edge_type_id,
        (select et.name as edge_type_name from graphs.node_edge_rels ner join graphs.edge_types et
        ON ner.edge_type_id=et.edge_type_id where ner.id=neslr.ne_id)
        as edge_type_name
        from 
        graphs.node_edge_sci_lit_rels neslr join source.sci_lits sl on neslr.pmid=sl.pmid 
        Join graphs.node_edge_rels ner on ner.id=neslr.ne_id ";
        // $sql = $sql . " ,sl.title,sl.publication_date"; //-- uncomment for additional pmid specific details along with join part
        // $sql = $sql . " from graphs.node_edge_sci_lit_rels neslr";
        // $sql = $sql . " join source.sci_lits sl on neslr.pmid=sl.pmid"; //-- uncomment for additional pmid specific details along with  ";

        $ne_ids = collect($request->ne_ids);
        $ne_idsImplode = $ne_ids->implode(', ');
        if (!empty($ne_idsImplode))
            $sql = $sql . " where neslr.ne_id in (" . $ne_idsImplode . ")"; // pass node-node relation type id

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and ner.edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1 or 2 and above

        $sql = $sql . " order by publication_date desc ";

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'pmidListsSentence' => $result
        ]);
    }

    public function getPMIDListsInRelation(Request $request)
    {
        $sql = "select distinct neslr.pmid, sl.title, sl.publication_date from graphs.node_edge_rels ndr join graphs.node_edge_sci_lit_rels neslr on ndr.id = neslr.ne_id join source.sci_lits sl on neslr.pmid = sl.pmid where 1=1 ";

        if ($request->source_node!='')
            $sql = $sql . " and ndr.source_node = " . $request->source_node; // pass source-node relation

        if ($request->destination_node!='')
            $sql = $sql . " and ndr.destination_node = " . $request->destination_node; // pass destination-node relation

        // if ($request->nnrt_id!='')
        //     $sql = $sql . " and ndr.nnrt_id =" . $request->nnrt_id; // pass node select relation
            
        if ($request->edge_type_id!='')
            $sql = $sql . " and ndr.edge_type_id = " . $request->edge_type_id; // pass edge type id

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'pmidLists' => $result
        ]);
    }

    public function getEdgePMIDCount(Request $request)
    {
        $sql = "select count(distinct neslr.pmid) as pmid_count from graphs.node_edge_sci_lit_rels neslr join source.sci_lits sl on neslr.pmid=sl.pmid Join graphs.node_edge_rels ner on ner.id=neslr.ne_id "; //-- uncomment for additional pmid specific details along with join part
        // $sql = "select count(neslr.ne_id) as pmid_count from graphs.node_edge_sci_lit_rels neslr join source.sci_lits sl on neslr.pmid=sl.pmid  "; //-- uncomment for additional pmid specific details along with join part
        $ne_ids = collect($request->edge_type_pmid);
        $ne_idsImplode = $ne_ids->implode(', ');
        if (!empty($ne_idsImplode))
            $sql = $sql . " where neslr.ne_id in (" . $ne_idsImplode . ")"; // pass node-node relation type id

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " and ner.edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1 or 2 and above
                
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'pmidCount' => $result
        ]);
    }

    public function getEvidenceData(Request $request){
        $ne_id = $request->ne_id;
        //$sql = "select evidence_data from graphs.evidence_metadata_details where ne_id in (".$ne_id.")";
        //$sql = "select evidence_data from graphs.evidence_metadata_details where ne_id in (208567)";
        $sql = "select a.gene_symbol_e1, a.gene_symbol_e2, a.e1_type_name, a.e2_type_name, a.edge_name, a.pubmed_id,
                b.sentence
                from 
                graphs.evidence_metadata_details a, 
                onto_model_source.relation_extraction_outputs b
                where 
                a.ne_id in (".$ne_id.")
                and 
                b.rel_extract_id = a.rel_extract_id
                and a.rel_extract_id!= 1";
        //echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'evidence_data' => $result
        ]);
    }

    //1 CT API
    public function getCTDiseaseAssoc(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                // $sourceNode = collect($request->source_node);
                // $sourceNodeImplode = $sourceNode->implode(', ');       
                // if (!empty($sourceNodeImplode))
                //     $sql = $sql . " where ctdr.node_id in  (" . $sourceNodeImplode . ")"; // pass node-node relation type id

                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
                // if($request->destination_node_all != 1){
                //     $destinationNode = collect($request->destination_node);
                //     $destinationNodeImplode = $destinationNode->implode(', ');
                //     //echo "heree2: " . $destinationNodeImplode;
                //     if (!empty($destinationNodeImplode)){
                //        $destinationNodeImplode = $destinationNodeImplode;
                //        $sql = $sql . " where ctdr.node_id in (".$sourceNodeImplode.",".$destinationNodeImplode.")";
                //     }else{
                //         $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                //         $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                //         $sql = $sql . " where ctdr.node_id in (".$sourceNodeImplode.",".$destinationNodeAllCTImplode.")";
                //     }                    
                // }else{
                //     $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                //     $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                //     $sql = $sql . " where ctdr.node_id in (".$sourceNodeImplode.",".$destinationNodeAllCTImplode.")";
                // }
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "select  n.node_id,n.name as disease_name,
                ct.ct_id,ct.nct_id,ct.org_study_id,ct.secondary_study_id,ct.title,ct.overall_status,pm.phase_id,pm.name as phase_name,
                ct.has_expanded_access,ct.minimum_age,ct.maximum_age,ct.healthy_volunteers,ct.varification_date,ct.study_first_submitted,
                ct.study_first_posted,ct.last_update_submitted,ct.last_update_submitted_qc,ct.study_type,ct.gender,ct.study_first_submitted_qc,
                ct.trial_design,
                array_agg(ctir.tit_id) as associated_tit_ids,
                array_agg(ctpr.pmid) as associated_pmids
                from graphs.clinical_trial_disease_rels ctdr
                join source.clinical_trial ct on ctdr.ct_id=ct.ct_id
                join graphs.nodes n on ctdr.node_id=n.node_id
                join ontology.phase_master pm on ct.phase_id=pm.phase_id
                left join graphs.clinical_trial_intervention_rels ctir on ctir.ct_id=ct.ct_id
                left join graphs.clinical_trial_pmid_rels ctpr on ctpr.ct_id=ct.ct_id ";
                $sql = $sql."where ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql."group by 1,3,9 limit 100";
                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTDATA' => ""
                ]);
            }
        }     
    }

    //2 ct API
    public function getCTTrialInvestRels(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "with cte (ct_id,node_id,name) as
                (
                select ctdr.ct_id,ctdr.node_id,n.name from graphs.clinical_trial_disease_rels ctdr
                join graphs.nodes n on ctdr.node_id=n.node_id";
                
                $sql = $sql." WHERE ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql. "), reference_data (node_id,disease_name,ct_id,nct_id,investigator_id,investigator_name,affiliation,investigator_role_id,role,country_id,country_name) as (select distinct c.node_id,c.name as disease_name,ct.ct_id,ct.nct_id,im.investigator_id,im.name as investigator_name,im.affiliation,irm.investigator_role_id,irm.name as role,cm.country_id,cm.name as country_name from cte c join source.clinical_trial ct on c.ct_id=ct.ct_id join graphs.clinical_trial_investigator_rels ctirs on ctirs.ct_id=ct.ct_id join graphs.investigator_master im on im.investigator_id=ctirs.investigator_id join graphs.investigator_role_master irm on irm.investigator_role_id=im.investigator_role_id join graphs.country_master cm on ctirs.country_id=cm.country_id) select rd.*,a.pmids from reference_data rd left join lateral (select ctpr.ct_id,array_agg(ctpr.pmid) pmids from graphs.clinical_trial_pmid_rels ctpr where ctpr.ct_id=rd.ct_id group by 1) as a on true ";

                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTRelsDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTRelsDATA' => ""
                ]);
            }
        }
        // echo $sql;       
    }

    //3 ct API Investigator Name
    public function getCTInvestigatorName(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "with cte (ct_id) as (select distinct ctdr.ct_id from graphs.clinical_trial_disease_rels ctdr";                
                $sql = $sql." WHERE ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql. "), reference_data (investigator_id,investigator_name,count_nct_ids) as (select im.investigator_id,im.name as investigator_name,count(distinct ctirs.ct_id) from cte c join graphs.clinical_trial_investigator_rels ctirs on ctirs.ct_id=c.ct_id join graphs.investigator_master im on im.investigator_id=ctirs.investigator_id group by 1) select * from reference_data ";
                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTInvestigatorNameDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTInvestigatorNameDATA' => ""
                ]);
            }
        }
        // echo $sql;       
    }

    //4. API for Tunnel chart in investigation Role
    public function getCTInvestigatorRole(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "with cte (ct_id) as (select distinct ctdr.ct_id from graphs.clinical_trial_disease_rels ctdr ";                
                $sql = $sql." WHERE ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql. "),reference_data (investigator_id,investigator_name,count_nct_ids) as (select irm.investigator_role_id,irm.name as role_name,count(distinct ctirs.investigator_id) from cte c join graphs.clinical_trial_investigator_rels ctirs on ctirs.ct_id=c.ct_id join graphs.investigator_master im on im.investigator_id=ctirs.investigator_id join graphs.investigator_role_master irm on im.investigator_role_id=irm.investigator_role_id group by 1) select * from reference_data order by count_nct_ids desc";
                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTInvestigatorRoleDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTInvestigatorRoleDATA' => ""
                ]);
            }
        }
        // echo $sql;       
    }

    //5. API for Word Map in investigation Country
    public function getCTInvestigatorCountry(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "with cte (ct_id) as (select distinct ctdr.ct_id from graphs.clinical_trial_disease_rels ctdr ";                
                $sql = $sql." WHERE ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql. "),reference_data (investigator_id,investigator_name,count_nct_ids) as (select irm.investigator_role_id,irm.name as role_name,count(distinct ctirs.investigator_id) from cte c join graphs.clinical_trial_investigator_rels ctirs on ctirs.ct_id=c.ct_id join graphs.investigator_master im on im.investigator_id=ctirs.investigator_id join graphs.investigator_role_master irm on im.investigator_role_id=irm.investigator_role_id group by 1) select * from reference_data order by count_nct_ids desc";
                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTInvestigatorCountryDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTInvestigatorCountryDATA' => ""
                ]);
            }
        }
        // echo $sql;       
    }

    //6 ct API Investigator Rels By Stats
    public function getCTInvestigatorRelsByStats(Request $request)
    {
        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');  

        $sql2 = "with cte (source_node, destination_node) as (select distinct source_node, destination_node FROM graphs.node_edge_rels WHERE 1=1";

        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql2 = $sql2 . " AND edge_type_id in (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1
    
        if($request->destination_node_all != 1){
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');
            //echo "heree2: " . $destinationNodeImplode;
            if (!empty($destinationNodeImplode)){
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeImplode.")"; // pass node-node relation type id
            }else{
                $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
                $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
                if (!empty($destinationNodeAllCTImplode))
                $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
            }
        }else{
            $destinationNodeAllCT = collect($request->destination_node_all_for_ct);
            $destinationNodeAllCTImplode = $destinationNodeAllCT->implode(', ');
            if (!empty($destinationNodeAllCTImplode))
            $sql2 = $sql2 . " AND source_node in (".$sourceNodeImplode.") and destination_node in (".$destinationNodeAllCTImplode.")"; // pass node-node relation type id
        }
        $sql2 = $sql2 . " )";

        if($request->nnrt_id2 == ""){  // For level 1 Check
            //For First Level Data Show
            if($request->nnrt_id==2){
                $sql2 = $sql2 . " select distinct destination_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==3){
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte";
            }
            else if($request->nnrt_id==4){
                $sourceNode = collect($request->source_node);
                $sourceNodeImplode = $sourceNode->implode(', ');       
                $sql2 = $sql2 . " select distinct source_node as selected_nodes from cte union select distinct destination_node as selected_nodes from cte";
            }
            // echo $sql2;
            $result2 = DB::select($sql2);            
            $diseaseNodes_ids = array();
            foreach ($result2 as $value) {
                $diseaseNodes_ids[] = $value->selected_nodes;
            }
            $diseaseNodesId = collect($diseaseNodes_ids);
            $diseaseNodesIdRelevantIds = $diseaseNodesId->implode(', ');       

            if(count($diseaseNodes_ids) > 0){
                $sql = "with cte (ct_id) as (select distinct ctdr.ct_id from graphs.clinical_trial_disease_rels ctdr ";                
                $sql = $sql." WHERE ctdr.node_id in (".$diseaseNodesIdRelevantIds.")";
                $sql = $sql. "),reference_data (investigator_id,investigator_name,count_nct_ids,count_pm_ids) as (select im.investigator_id,im.name as investigator_name,count(distinct ctirs.ct_id),count(distinct ctpr.pmid) from cte c join graphs.clinical_trial_investigator_rels ctirs on ctirs.ct_id=c.ct_id join graphs.investigator_master im on im.investigator_id=ctirs.investigator_id left join graphs.clinical_trial_pmid_rels ctpr on ctpr.ct_id=ctirs.ct_id group by 1) select * from reference_data ";
                // echo $sql;
                $result = DB::select($sql);
                return response()->json([
                    'CTInvestigatorRelsByStatsDATA' => $result
                ]);
            }
            else{
                return response()->json([
                    'CTInvestigatorRelsByStatsDATA' => ""
                ]);
            }
        }
        // echo $sql;       
    }

}
