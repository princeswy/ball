<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/api/getMatchList', 'FmatchController@show');
$router->get('/api/getMissplayer', 'FmatchController@missplayer');
$router->get('/api/getMatch_lineup','FmatchController@match_lineup');
$router->get('/api/getScoretable','FscoretableController@index');
$router->get('/api/getFscoretableMatch','FscoretableController@match');

$router->get('/api/getShooters','FshootersController@index');
$router->get('/api/getPlayercount','FplayercountController@index');
$router->get('/api/getMatchInfo','FmatchController@match_info');
$router->get('/api/getLeaguelist','HomeController@league_list');
$router->get('/api/getSeasonlist','HomeController@season_list');



$router->get('/api/history_match','FmatchController@history_match');
$router->get('/api/home_history_match','FmatchController@home_history_match');
$router->get('/api/future_match','FmatchController@future_match');
$router->get('/api/match_lineup','FmatchController@match_lineup');

$router->get('/api/getOddsList','FoddsController@odds_list');
$router->get('/api/getHandOddsList','FhandoddsController@odds_list');
$router->get('/api/getMatchDetail','FdetailController@index');
$router->get('/api/getMatchStatis','FstatisController@index');
$router->get('/api/getRecommendList','FmatchController@recommend_list');
//篮球技术统计
//
$router->get('/api/getBstatistics','BstatisticsController@index');
$router->get('/api/getBstatisTeam','BstatisticsController@Bstatis_team');
$router->get('/api/getBstatisplayer','BstatisticsController@Bstatisplayer');
$router->get('/api/getBmatchOddsList','BoddsController@odds_list');
$router->get('/api/getStatisplayerShoot','BstatisticsController@StatisplayerShoot');
$router->get('/api/getBmatchlineup','BmatchlineupController@index');


//篮球赛事赛果相关
$router->get('/api/getMonth_data','BmatchController@Month_data');
$router->get('/api/getBmatch','BmatchController@index');
$router->get('/api/getSection_list','HomeController@section_list');
$router->get('/api/getGroup_list','HomeController@group_list');
