<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Bleague;
use App\models\Bplayer;
use App\models\Bseason;
use App\models\Bteam;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class lqInitTask extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lq:init  {--type=} {--team_type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化球探篮球数据';
    public static $team_url = "http://interface.win007.com/basketball/team.aspx";

    public static $player_url = "http://interface.win007.com/basketball/player.aspx";

    public static $league_url = "http://interface.win007.com/basketball/league.aspx";

    public static $source = 'win007';

    public function handle () {
        $type = $this->option('type');

        if ( !$type ) {
            $this->info("!type");
            return false;
        }
        switch ( $type ) {

            case 'team' :
                $this->Handle_Team();
                break;
            case 'player' :
                $this->Handle_Player();
                break;
            case 'league' :
                $this->Handle_League();
                break;

        }
    }

    public function Handle_League(){

        $res = self::send_request(self::$league_url);
        $resData = json_decode($res['content']);

        $seasons = array();
        $leagues = Bleague::convert_qtLeague($resData, $seasons);
        if(!$leagues){
            $this->info('暂无数据处理');
            return false;
        }

        $tiao = 0;
        $add = 0;
        $this->info('共'.count($leagues).'条数据，开始处理');
        foreach ( $leagues as $key => $val ) {

            $where = [
                'out_league_id' => $val['out_league_id']
            ];

            $in_data = [
                'league_id' => $val['out_league_id'],
                'out_league_id' => $val['out_league_id'],
                'league_name' => $val['league_name'],
                'full_name' => $val['full_name'],
                'full_name_hk' => $val['full_name_hk'],
                'full_name_en' => $val['full_name_en'],
                'type' => $val['type'],
                'league_color' => $val['league_color'],
                'country_id' => $val['country_id'],
                'country_name' => $val['country'],
                "sclass_kind" => $val['sclass_kind'],
            ];

            Bleague::updateOrCreate($where, $in_data);

        }

        Bseason::insert_seasons($seasons);

        $this->info('总共' . count($leagues) . '条,已增加' . $add . '条,已跳过'.$tiao.'条');

    }

    public function Handle_Team()
    {
        $res = self::send_request(self::$team_url);
        $resData = json_decode($res['content']);

        $team_datas = $resData->TeamList;
        if(!$team_datas){
            $this->info('暂无数据处理');
            return false;
        }
        $bteam_add = 0;
        $bteammap_tiao = 0;
        $bteam_zong = count($team_datas);
        $this->info('共'.count($team_datas).'条数据，开始处理');
        foreach($team_datas as $key => $val){
            $where = [
                'out_team_id' => $val->teamId,
            ];
            $in_data = [
                'out_team_id' => $val->teamId,
                'short' => $val->nameChsShort,
                'shortname' => $val->nameChs,
                'name_hk' => $val->nameChtShort,
                'name_en' => $val->nameEnShort,
                'team_logo' => isset($val->logo) ? $val->logo.'?win007=sell' : '',
                'team_url' => $val->website,
                'league_id' => $val->leagueId,
                'division_name' => $val->divisionCn,
                'city_name' => $val->cityCn,
                'gym_name' => $val->gymnasiumCn,
                'capacity' => $val->capacity,
                'join_year' => $val->joinYear,
                'master' => $val->headCoachCn,
                'source' => 'win007'
            ];

            Bteam::updateOrCreate($where, $in_data);

        }
        $this->info('总共' . $bteam_zong . '条,已增加' . $bteam_add . '条,已跳过'.$bteammap_tiao.'条');

    }

    public function Handle_Player()
    {

        $res = self::send_request(self::$team_url);
        $resData = json_decode($res['content']);

        $player_datas = $resData->list;

        if(!$player_datas){
            $this->info('暂无数据处理');
            return false;
        }else{
            $this->info('共'.count($player_datas).'条数据，开始处理');
            foreach ($player_datas as $key => $val) {
                $team_data = Bteam::where('out_team_id', $val->teamId)->first();
                $team_id = $team_data ? $team_data->toArray()['id'] : 0;
                $where = [
                    'out_player_id' => $val->playerId,
                ];
                $in_data = [
                    'out_player_id' => $val->playerId,
                    'player_name' => $val->nameChs,
                    'short' => $val->nameChsShort,
                    'player_name_en' => $val->nameEn,
                    'player_name_hk' => $val->nameCht,
                    'number' => $val->number,
                    'place' => $val->placeCn,
                    'birthday' => $val->birthday,
                    'height' => $val->height,
                    'weight' => $val->weight,
                    'photo' => $val->photo,
                    'nbaage' => $val->nbaAge,
                    'salary' => $val->salary,
                    'team_id' => $team_id,
                    'out_team_id' => $val->teamId
                ];

                Bplayer::updateOrCreate($where, $in_data);
            }
        }

    }

    /**
     * @param $Url
     * @param string $Method
     * @param array $Parameter
     * @param array $Header
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function send_request($Url, $Method = 'GET', $Parameter = [], $Header = []) {

        if ( !$Url ) {
            return false;
        }

        $option['connect_timeout'] = 30;
        $option['timeout'] = 30;

        if ( $Parameter ) {

            $option['query'] = $Parameter;

        }

        try {
            $client = new Client($option);
            $request = new Request($Method, $Url, $Header);
            $response = $client->send($request);
            $body = $response->getBody();
            $content = $body->getContents();
            $HTTP_Code = $response->getStatusCode();
        } catch (RequestException $e) {
            $content = $e->getMessage();
            $HTTP_Code = $e->getCode();

        }

        return [ 'content' => $content, 'HTTP_Code' => $HTTP_Code ];
    }
}