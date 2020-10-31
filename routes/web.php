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
