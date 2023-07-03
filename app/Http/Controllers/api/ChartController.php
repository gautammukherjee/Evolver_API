<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function distributionByRelationGrp(Request $request){

        //echo "edge_select:".$request->input('edge_select');
        list($day,$month,$year) = explode("-",$request->input('from_date'));
        $from_date = $year.'-'.$month.'-'.$day;
        //echo $from_date;

        $sql = "SELECT \"source\".\"Temp Edge Types_Name\" AS \"Temp Edge Types_Name\", COUNT(*) AS \"count\"
        FROM (SELECT sl.\"pmid\" AS \"pmid\", sl.\"publication_date\" AS \"publication_date\", sl.\"title\" AS \"title\", neslr.\"pmid\" AS \"Node Edge Sci Lit Rels__pmid\",
        nnrtn.\"name\" AS \"Node Node Relation Types\", 
        nsn.\"name\" AS \"Source_Node_Name\", 
        ndn.\"name\" AS \"Destination_Node_Name\", 
        et.\"name\" AS \"Edge Types_Name\",
        tet.\"group_name\" AS \"Temp Edge Types_Name\"
        FROM \"source\".\"sci_lits\" as sl
        INNER JOIN \"graphs\".\"node_edge_sci_lit_rels\" AS neslr ON sl.\"pmid\" = neslr.\"pmid\"
        LEFT JOIN \"graphs\".\"node_edge_rels\" AS nern ON neslr.\"ne_id\" = nern.\"id\" 
        LEFT JOIN \"graphs\".\"node_node_relation_types\" AS nnrtn ON nern.\"nnrt_id\" = nnrtn.\"nnrt_id\" 
        LEFT JOIN \"graphs\".\"nodes\" AS nsn ON nern.\"source_node\" = nsn.\"node_id\" 
        LEFT JOIN \"graphs\".\"node_edge_rels\" AS ner ON nern.\"id\" = ner.\"id\" 
        LEFT JOIN \"graphs\".\"nodes\" AS ndn ON nern.\"destination_node\" = ndn.\"node_id\" 
        LEFT JOIN \"graphs\".\"edge_types\" AS et ON nern.\"edge_type_id\" = et.\"edge_type_id\"
        LEFT JOIN \"graphs\".\"temp_edge_type_group\" AS tet ON tet.\"edge_type_id\" = nern.\"edge_type_id\"
        Where sl.publication_date > '2017-06-01'
           AND nsn.name <> ndn.name AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')


        ) AS \"source\"
        WHERE (\"source\".\"Node Node Relation Types\" = 'Gene To Disease') AND (\"source\".\"Source_Node_Name\" = 'PDCD1') AND (\"source\".\"Destination_Node_Name\" = 'ADENOCARCINOMA')
        GROUP BY \"source\".\"Temp Edge Types_Name\"
        ORDER BY \"source\".\"Temp Edge Types_Name\" ASC";

        //echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);

    } //distributionByRelationGrp() ends


    public function details_of_association_type(Request $request){
        $sql = "SELECT \"source\".\"Node Node Relation Types\" AS \"Node Node Relation Types\", COUNT(*) AS \"count\"
        FROM (SELECT sl.\"pmid\" AS \"pmid\", sl.\"publication_date\" AS \"publication_date\", sl.\"title\" AS \"title\", neslr.\"pmid\" AS \"Node Edge Sci Lit Rels__pmid\",
        nnrtn.\"name\" AS \"Node Node Relation Types\", 
        nsn.\"name\" AS \"Source_Node_Name\", 
        ndn.\"name\" AS \"Destination_Node_Name\", 
        et.\"name\" AS \"Edge Types_Name\",
        tet.\"group_name\" AS \"Temp Edge Types_Name\"
        FROM \"source\".\"sci_lits\" as sl
        INNER JOIN \"graphs\".\"node_edge_sci_lit_rels\" AS neslr ON sl.\"pmid\" = neslr.\"pmid\"
        LEFT JOIN \"graphs\".\"node_edge_rels\" AS nern ON neslr.\"ne_id\" = nern.\"id\" 
        LEFT JOIN \"graphs\".\"node_node_relation_types\" AS nnrtn ON nern.\"nnrt_id\" = nnrtn.\"nnrt_id\" 
        LEFT JOIN \"graphs\".\"nodes\" AS nsn ON nern.\"source_node\" = nsn.\"node_id\" 
        LEFT JOIN \"graphs\".\"node_edge_rels\" AS ner ON nern.\"id\" = ner.\"id\" 
        LEFT JOIN \"graphs\".\"nodes\" AS ndn ON nern.\"destination_node\" = ndn.\"node_id\" 
        LEFT JOIN \"graphs\".\"edge_types\" AS et ON nern.\"edge_type_id\" = et.\"edge_type_id\"
        LEFT JOIN \"graphs\".\"temp_edge_type_group\" AS tet ON tet.\"edge_type_id\" = nern.\"edge_type_id\"
        Where sl.publication_date > '2017-06-01'
           AND nsn.name <> ndn.name AND nsn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT') AND ndn.name NOT IN ('WAS','IMPACT', 'HR', 'SIT')


        ) AS \"source\"
        WHERE \"source\".\"Source_Node_Name\" = 'ADA'
        GROUP BY \"source\".\"Node Node Relation Types\"
        ORDER BY \"source\".\"Node Node Relation Types\" ASC";

        //echo $sql;
        $result = DB::select($sql);
        return response()->json([
            'nodeSelectsRecords' => $result
        ]);
    }


}
