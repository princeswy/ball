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

$router->any('/api/getMatchList', 'FmatchController@show');
$router->any('/api/getMissplayer', 'FmatchController@missplayer');
$router->any('/api/getMatch_lineup','FmatchController@match_lineup');
$router->any('/api/getScoretable','FscoretableController@index');
$router->any('/api/getShooters','FshootersController@index');
$router->any('/api/getPlayercount','FplayercountController@index');
$router->any('/api/getMatchInfo','MatchController@match_info');
$router->any('/api/getLeaguelist','HomeController@league_list');
$router->any('/api/getSeasonlist','HomeController@season_list');
