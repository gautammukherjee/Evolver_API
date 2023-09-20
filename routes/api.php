<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\NodeController;
use App\Http\Controllers\api\NodeRevampController;
use App\Http\Controllers\api\ChartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [UserController::class, 'login']);
// Route::post('/login','App\Http\Controllers\api\UserController@login');

Route::get('/getNodeSelects', [NodeController::class, 'getNodeSelects']);
Route::post('/getSourceNode', [NodeController::class, 'getSourceNode']);

Route::post('/getDestinationNode', [NodeController::class, 'getDestinationNode']);
Route::post('/getMasterLists', [NodeController::class, 'getMasterLists']);
Route::post('/getAllRecords', [NodeController::class, 'getAllRecords']);
Route::get('/getEdgeType', [NodeController::class, 'getEdgeType']);
Route::get('/getEdgeTypeFirst', [NodeController::class, 'getEdgeTypeFirst']);


Route::post('/distribution_by_relation_grp', [ChartController::class, 'distributionByRelationGrp']);
Route::post('/distribution_by_relation_grp_get_edge_type_drilldown', [ChartController::class, 'distribution_by_relation_grp_get_edge_type_drilldown']);
Route::post('/details_of_association_type', [ChartController::class, 'details_of_association_type']);
Route::post('/pmid_count_with_gene_disease', [ChartController::class, 'pmid_count_with_gene_disease']);

Route::post('/getEdgeTypeName', [NodeController::class, 'getEdgeTypeName']);
Route::post('/getEdgePMIDLists', [NodeController::class, 'getEdgePMIDLists']);
Route::post('/getEdgeTypeSentencePMIDLists', [NodeController::class, 'getEdgeTypeSentencePMIDLists']);
Route::post('/getDistributionRelationType', [NodeController::class, 'getDistributionRelationType']);
Route::post('/getEvidenceData', [NodeController::class, 'getEvidenceData']);

//2 level
Route::post('/getNodeSelects2', [NodeController::class, 'getNodeSelects2']);
Route::post('/getSourceNode2', [NodeController::class, 'getSourceNode2']);
Route::post('/getDestinationNode2', [NodeController::class, 'getDestinationNode2']);
Route::post('/getPMIDListsInRelation', [NodeController::class, 'getPMIDListsInRelation']);
Route::post('/getEdgePMIDCount', [NodeController::class, 'getEdgePMIDCount']);
Route::post('/getCTDiseaseAssoc', [NodeController::class, 'getCTDiseaseAssoc']);
Route::post('/getCTTrialInvestRels', [NodeController::class, 'getCTTrialInvestRels']);
Route::post('/getCTInvestigatorName', [NodeController::class, 'getCTInvestigatorName']);
Route::post('/getCTInvestigatorRole', [NodeController::class, 'getCTInvestigatorRole']);
Route::post('/getCTInvestigatorCountry', [NodeController::class, 'getCTInvestigatorCountry']);
Route::post('/getCTInvestigatorRelsByStats', [NodeController::class, 'getCTInvestigatorRelsByStats']);

//////////////// For revamp //////////////////////////
Route::post('/getMasterListsRevampLevelOne', [NodeRevampController::class, 'getMasterListsRevampLevelOne']);
Route::post('/getMasterListsRevampLevelTwo', [NodeRevampController::class, 'getMasterListsRevampLevelTwo']);
Route::post('/getMasterListsRevampLevelOneCount', [NodeRevampController::class, 'getMasterListsRevampLevelOneCount']);
Route::post('/getMasterListsRevampLevelTwoCount', [NodeRevampController::class, 'getMasterListsRevampLevelTwoCount']);
