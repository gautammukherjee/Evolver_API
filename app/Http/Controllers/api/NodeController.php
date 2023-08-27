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
            $sql = $sql . " and (n1.name ilike '%$request->searchval%' OR ns1.name ilike '%$request->searchval%')"; // search with source node
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
        $destinationNode = collect($request->destination_node);
        $destinationNodeImplode = $destinationNode->implode(', ');
        // echo "heree2: " . $destinationNodeImplode;
        if (!empty($destinationNodeImplode))
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

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
        $sql = $sql . " join graphs.node_syns ns2 on n2.node_id=ns2.node_id"; //(Uncomment when destination_node_synonym name searched)";

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
            $sql = $sql . " and (n2.name ilike '%$request->searchval%' OR ns2.name ilike '%$request->searchval%')"; //serach with destination node
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
            $sql = $sql . " and (n2.name ilike '%$request->searchval%' OR ns2.name ilike '%$request->searchval%')"; //serach with destination node
            // $sql = $sql . " and ns2.name ilike '%$request->searchval%' "; // search with synonym destination node
        }
        $sql = $sql . "order by destination_node_name";
        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'destinationNodeRecords2' => $result
        ]);
    }

    public function getMasterLists(Request $request)
    {
        //echo $request->
        // echo $request->source_node;
        // echo "<br/>" . $request->destination_node;
        // echo "<br/>" . $request->nnrt_id;
        // echo "<br/>" . $request->nnrt_id2;
        // echo "<br/>" . $request->edge_type_id;
        // echo "<br/>" . $request->edge_type_id2;
        // if ($request->source_node != "" && $request->source_node != 'undefined') {
        //     $sourceNodeImplode = implode(",", $request->source_node ?? []);
        //     $sql = $sql . " and source_node in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
        // } else {
        //     $sql = $sql . " and source_node in (32823,33163)";
        // }

        // if ($request->destination_node != "" && $request->destination_node != "undefined") {
        //     $destinationNodeImplode = implode(",", $request->destination_node ?? []);
        //     $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
        // } else {
        //     $sql = $sql . " and destination_node in (45136,25257,46776)";
        // }

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
        $destinationNode = collect($request->destination_node);
        $destinationNodeImplode = $destinationNode->implode(', ');
        // echo "heree2: " . $destinationNodeImplode;
        if (!empty($destinationNodeImplode))
            $sql = $sql . " and destination_node in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

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
        $sql = $sql . " group by 1,2,3,4,5,6,10 ) select * from relevant_data rd order by 5";
        // $sql = $sql ." offset 50";

        /*if (!empty($destinationNodeImplode))
            $sql = $sql . " limit 2000";
        else
            $sql = $sql . " limit 2000";
        */

        if ($request->offSetValue != "") {
            $sql = $sql . " offset " . $request->offSetValue;
        }

        if ($request->limitValue != "") {
            $sql = $sql . "limit " . $request->limitValue;
        }else{
            $sql = $sql . " limit 200";
        }
        // $sql = $sql . " limit 5";

        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'masterListsData' => $result
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
            $destinationNode = collect($request->destination_node);
            $destinationNodeImplode = $destinationNode->implode(', ');       
            if (!empty($destinationNodeImplode))
                $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id
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

        $sql = $sql . " ) AS source GROUP BY 1,2,3,4,5,6 ORDER BY source.source_node_id ASC ";
        // echo $sql;

        $result = DB::select($sql);
        return response()->json([
            'distributionData' => $result
        ]);
    }

    public function getEdgePMIDLists(Request $request)
    {
        $sql = "select distinct neslr.pmid, neslr.ne_id";
        $sql = $sql . " ,sl.title,sl.publication_date"; //-- uncomment for additional pmid specific details along with join part
        $sql = $sql . " from graphs.node_edge_sci_lit_rels neslr";
        $sql = $sql . " join source.sci_lits sl on neslr.pmid=sl.pmid"; //-- uncomment for additional pmid specific details along with  ";

        $ne_ids = collect($request->ne_ids);
        $ne_idsImplode = $ne_ids->implode(', ');
        if (!empty($ne_idsImplode))
            $sql = $sql . " where neslr.ne_id in (" . $ne_idsImplode . ")"; // pass node-node relation type id

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'pmidLists' => $result
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
        $sql = "select count(neslr.pmid) as pmid_count "; //-- uncomment for additional pmid specific details along with join part
        $sql = $sql . " from graphs.node_edge_sci_lit_rels neslr";
        $sql = $sql . " join source.sci_lits sl on neslr.pmid=sl.pmid"; //-- uncomment for additional pmid specific details along with  ";

        $ne_ids = collect($request->edge_type_pmid);
        $ne_idsImplode = $ne_ids->implode(', ');
        if (!empty($ne_idsImplode))
            $sql = $sql . " where neslr.ne_id in (" . $ne_idsImplode . ")"; // pass node-node relation type id

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
                a.ne_id in (208567)
                and 
                b.rel_extract_id = a.rel_extract_id
                and a.rel_extract_id!= 1";
        //echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'evidence_data' => $result
        ]);
    }
}
