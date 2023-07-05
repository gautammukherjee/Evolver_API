<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function distributionByRelationGrp(Request $request)
    {
        // //echo "edge_select:".$request->input('edge_select');
        // list($day, $month, $year) = explode("-", $request->input('from_date'));
        // $from_date = $year . '-' . $month . '-' . $day;
        //echo $from_date;

        $sql = "SELECT source.edge_type_id, source.Temp_Edge_Types_Name AS Temp_Edge_Types_Name, COUNT(*) as count
		FROM (SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, 
        neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types, 
        nnrtn.nnrt_id,	  
        nsn.name AS Source_Node_Name,
        nsn.node_id as source_node_id,	  
        ndn.name AS Destination_Node_Name,
        ndn.node_id as destination_node_id,	  
        et.name AS Edge_Types_Name,
	    tet.edge_type_id,
        tet.group_name AS Temp_Edge_Types_Name FROM source.sci_lits as sl 
        INNER JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid = neslr.pmid
        JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id 
        JOIN graphs.node_node_relation_types AS nnrtn ON nern.nnrt_id = nnrtn.nnrt_id 
        JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id 
        JOIN graphs.node_edge_rels AS ner ON nern.id = ner.id 
        JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id 
        JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id
        LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id ";

        // $sql = $sql . "Where sl.publication_date > '2017-06-01' AND";

        $sql = $sql . " Where ";
        $sql = $sql . " nsn.node_id <> ndn.node_id ";
        // $sql = $sql . " AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')";

        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        // echo "heree2: " . $sourceNodeImplode;
        if (!empty($sourceNodeImplode))
            $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
        // }

        //2. Destination Node
        $destinationNode = collect($request->destination_node);
        $destinationNodeImplode = $destinationNode->implode(', ');
        // echo "heree2: " . $destinationNodeImplode;
        if (!empty($destinationNodeImplode))
            $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

        //3. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        //4. Node select level 1
        if ($request->nnrt_id != "") {
            $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }

        $sql = $sql . " ) AS source";
        $sql = $sql . " GROUP BY 1,2 ORDER BY 1 ASC";
        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);

    } //distributionByRelationGrp() ends


    public function details_of_association_type(Request $request)
    {
        $sql = "SELECT source.nnrt_id,  
        source.Node_Node_Relation_Types AS Node_Node_Relation_Types,
        COUNT(*) AS count
        FROM (SELECT sl.pmid AS pmid, sl.publication_date AS publication_date, sl.title AS title, 
        neslr.pmid AS Node_Edge_Sci_Lit_Rels_pmid,
        nnrtn.name AS Node_Node_Relation_Types, 
        nnrtn.nnrt_id,	  
        nsn.name AS Source_Node_Name,
        nsn.node_id as source_node_id,	  
        ndn.name AS Destination_Node_Name,
        ndn.node_id as destination_node_id,	  
        et.name AS Edge_Types_Name,
        tet.group_name AS Temp_Edge_Types_Name FROM source.sci_lits as sl 
        INNER JOIN graphs.node_edge_sci_lit_rels AS neslr ON sl.pmid = neslr.pmid
        JOIN graphs.node_edge_rels AS nern ON neslr.ne_id = nern.id 
        JOIN graphs.node_node_relation_types AS nnrtn ON nern.nnrt_id = nnrtn.nnrt_id 
        JOIN graphs.nodes AS nsn ON nern.source_node = nsn.node_id 
        JOIN graphs.node_edge_rels AS ner ON nern.id = ner.id 
        JOIN graphs.nodes AS ndn ON nern.destination_node = ndn.node_id 
        JOIN graphs.edge_types AS et ON nern.edge_type_id = et.edge_type_id
        LEFT JOIN graphs.temp_edge_type_group AS tet ON tet.edge_type_id = nern.edge_type_id ";

        $sql = $sql . " Where ";
        // -- sl.publication_date > '2017-06-01 and ' 
        $sql = $sql . " nsn.node_id <> ndn.node_id";
        // $sql = $sql . " AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')";

        $sourceNode = collect($request->source_node);
        $sourceNodeImplode = $sourceNode->implode(', ');
        // echo "heree2: " . $sourceNodeImplode;
        if (!empty($sourceNodeImplode))
            $sql = $sql . " AND nsn.node_id in (" . $sourceNodeImplode . ")"; // pass node-node relation type id
        // }

        //2. Destination Node
        $destinationNode = collect($request->destination_node);
        $destinationNodeImplode = $destinationNode->implode(', ');
        // echo "heree2: " . $destinationNodeImplode;
        if (!empty($destinationNodeImplode))
            $sql = $sql . " AND ndn.node_id in (" . $destinationNodeImplode . ")"; // pass node-node relation type id

        //3. Edge level 1
        $edgeType = collect($request->edge_type_id);
        $edgeTypeImplode = $edgeType->implode(', ');
        // echo "heree3: " . $edgeTypeImplode;
        if (!empty($edgeTypeImplode))
            $sql = $sql . " AND nern.edge_type_id IN (" . $edgeTypeImplode . ")"; //pass edge_type_id for Level 1

        //4. Node select level 1
        if ($request->nnrt_id != "") {
            $sql = $sql . " AND nern.nnrt_id = " . $request->nnrt_id; // pass node-node relation type id
        }
        $sql = $sql . " ) AS source";
        $sql = $sql . " GROUP BY 1,2 ORDER BY 1 ASC";

        // echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);
    }


}