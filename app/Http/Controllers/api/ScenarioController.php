<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ScenarioController extends Controller
{
    //Get Per User Scenario Count
    public function getPerUserScenarios(Request $request)
    {
        // $userId = auth()->user()->id;
        $userId = $request->user_id;
        // $email = $request->email;
        // $password = md5($request->password);
        $sql = "select count(*) as count FROM scenarios as s WHERE s.deleted = 0 and s.user_id =".$userId;
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        // print_r($result);
        
        // $result = DB::select($sql);
        return response()->json([
            'totalScenariosPerUser' => $result
        ]);
    }

    //Add Scenario
    public function addUserScenario(Request $request){
        // Checked condition when we insert into filter criteria into the user_filter_criterias table
        // if (!('multidelete' in Object.prototype)) {
        //   Object.defineProperty(Object.prototype, 'multidelete', {
        //     value: function () {
        //       for (var i = 0; i < arguments.length; i++) {
        //         delete this[arguments[i]];
        //       }
        //     }
        //   });
        // }
        // delete scenario.filter_criteria["single_ta_id"];
      
        // if (parseInt(scenario.page_id) == 7) {
        //   scenario.filter_criteria.multidelete('ta_id', 'di_ids', 'single_ta_id', 'ct_di_ids');
        // } else if (parseInt(scenario.page_id) == 1 || parseInt(scenario.page_id) == 2 || parseInt(scenario.page_id) == 4 || parseInt(scenario.page_id) == 5 || parseInt(scenario.page_id) == 8 || parseInt(scenario.page_id) == 9 || parseInt(scenario.page_id) == 10 || parseInt(scenario.page_id) == 11) {
        //   scenario.filter_criteria.multidelete('ta_id_dashboard', 'di_ids_dashboard', 'single_ta_id', 'ct_di_ids');
        // } else if (parseInt(scenario.page_id) == 3) {
        //   scenario.filter_criteria.multidelete('ta_id_dashboard', 'di_ids_dashboard', 'ta_id', 'di_ids', 'single_ta_id');
        // }
        //End here
      
        $scenario = $request;
        // echo "scenario1: ", $scenario;
        // echo "scenario2: ", $scenario->user_id['user_id'];

        $sql = "INSERT INTO scenarios (user_id,scenario_name,filter_criteria, comments) 
        values ('".$scenario->user_id['user_id']."','".$scenario->filter_name."','".json_encode(($scenario->filter_criteria))."','".$scenario->user_comments."')";
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenarioAdded' => $result
        ]);
       
    }

    // Get User Scenario
    public function getUserScenarios(Request $request){
        $userId = $request->user_id;
        $sql = "select u.user_name, s.id, s.user_id, s.scenario_name, s.filter_criteria, s.comments, s.created_at FROM scenarios as s LEFT JOIN users as u on s.user_id=u.user_id 
        WHERE s.deleted = 0 and s.user_id =".$userId." order by id";
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenarios' => $result
        ]);

    }

    // Delete User Scenario
    public function delUserScenario(Request $request){

        if ($request->scenario_id != "undefined")
            $scenarioID = $request->scenario_id;
        else
            $scenarioID = 0;

        $userId = $request->user_id;
        // echo $userId;
        // echo $scenarioID;

        // $sql = "UPDATE scenarios set deleted = 1 where id=" . $scenarioID . " and user_id =".$userId;
        $sql = "DELETE FROM scenarios where id=" . $scenarioID . " and user_id =".$userId;
        // echo $sql;

        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenariosDel' => $result
        ]);

    }

}