<?php
/**
 * Created by PhpStorm.
 * User: songwy
 * Date: 2020-10-19
 * Time: 14:59
 */
namespace App\Console\Commands;

use App\models\Bleague;
use App\models\Bmatch;
use App\models\Bplayer;
use App\models\Bseason;
use App\models\Bstatisplayer;
use App\models\Bstatisteam;
use App\models\Bstatistics;
use App\models\Bteam;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;

class lqStatistics extends  Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lq:lqStatistics {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取篮球技术统计';

    public static $url = "http://interface.win007.com/basketball/stats.aspx";
    public static $source = 'win007';

    public function handle () {
        $url = self::$url;
        $date = $this->option('date') ? $this->option('date') : '';
        $script_name = $this->signature;
        if ($date) {
            $url = self::$url.'?date='.$date;
            $script_name = $script_name.' --date='.$date;
        }
        self::check_process_num($script_name) || exit('Process limit');
        $res = self::send_request($url);
        $out_data = json_decode($res['content']);

        if (!isset($out_data->matchList) || count($out_data->matchList) === 0) {
            $this->info('暂无数据处理');
            return false;
        }
        $out_matchid_map = array_column($out_data->matchList, 'matchId');
        $home_name_map = array_column($out_data->matchList, 'homeTeamCn');
        $guest_name_map = array_column($out_data->matchList, 'awayTeamCn');
        $team_name_map = array_merge($home_name_map, $guest_name_map);
        $team_id_map = [];
        $match_id_map = [];
        $team_data = Bteam::whereIn('shortname', $team_name_map)->get(['id', 'shortname', 'short']);
        $match_data = Bmatch::whereIn('out_match_id', $out_matchid_map)->get(['id', 'out_match_id']);
        if (!$match_data) {
            return false;
        }
        $match_id_map = array_column($match_data->toArray(), 'id', 'out_match_id');
        if ($team_data) {
            foreach ($team_data->toArray() as $k => $v) {
                $team_id_map[$v['shortname']] = [
                    'id' => $v['id'],
                    'short' => $v['short'],
                    'shortname' => $v['shortname']
                ];
            }
        }
        foreach ($out_data->matchList as $key => $val) {
            $match_id = $match_id_map[$val->matchId];
            $where = [
                'match_id' => $match_id,
            ];
            $home_id = $this->save_team($val->homeTeamCn);
            $guest_id = $this->save_team($val->awayTeamCn);
            $datas = [
                'match_id' => $match_id,
                'out_match_id' => $val->matchId,
                'home_id' => $home_id,
                'guest_id' => $guest_id,
                'home_name' => isset($team_id_map[$val->homeTeamCn]) ? $team_id_map[$val->homeTeamCn]['short'] : $val->homeTeamCn,
                'guest_name' => isset($team_id_map[$val->awayTeamCn]) ? $team_id_map[$val->awayTeamCn]['short'] : $val->awayTeamCn,
                'home_score' => $val->homeScore,
                'guest_score' => $val->awayScore,
                'home_fast' => $val->homeFast,
                'guest_fast' => $val->awayFast,
                'home_inside' => $val->homeInside,
                'guest_inside' => $val->awayInside,
                'home_exceed' => $val->homeExceed,
                'guest_exceed' => $val->awayExceed,
                'home_totalmis' => $val->homeTotalmis,
                'guest_totalmis' => $val->awayTotalmis,
                'cost_time' => $val->costTime,
                'source' => self::$source
            ];

            if (isset($val->homePlayerList)) {
                $home_players = $val->homePlayerList;
            }

            if (isset($val->awayPlayerList)) {
                $guest_players = $val->awayPlayerList;
            }

            try {
                # 新增
                DB::beginTransaction();
                Bstatistics::updateOrCreate($where,$datas);

                $in_teamstatic_map = [];
                if($home_players){
                    $home_s2num = $home_s2hit = $home_s3num = $home_s3hit = $home_sbnum = $home_sbhit = $home_ords = $home_drds = $home_assists = $home_fouls = $home_steals = $home_tbshots = $home_turnovers = 0;
                    foreach($home_players as $kh => $vh){

                        $whereup = [
                            'match_id' => $match_id,
                            'player_name' => $vh->playerChs
                        ];
                        $home_attr = [
                            'match_id' => $match_id,
                            'team_id' => $home_id,
                            'is_host' => 1,
                            'player_id' => $this->save_player($vh->playerId, $vh->playerChs),
                            'out_match_id' => $val->matchId,
                            'out_player_id' => $vh->playerId,
                            'player_name' => $vh->playerChs,
                            'location' => $vh->positionCn,
                            'playtime' => $vh->playtime,
                            'shoot_hit' => $vh->shootHit,
                            'shoot' => $vh->shoot,
                            'threemin_hit' => $vh->threePointHit,
                            'threemin' => $vh->threePointShoot,
                            'punishball_hit' => $vh->freeThrowHit,
                            'punishball' => $vh->freeThrowShoot,
                            'attack' => $vh->offensiveRebound,
                            'defend' => $vh->defensiveRebound,
                            'helpattack' => $vh->assist,
                            'foul' => $vh->foul,
                            'rob' => $vh->steal,
                            'misplay' => $vh->turnover,
                            'cover' => $vh->block,
                            'score' => $vh->score,
                            'is_onfool' => $vh->isOnFloor ? 1 : 0,
                            'source' => self::$source
                        ];

                        #球队技术统计
                        if ( $vh->isOnFloor ) {
                            $home_s2num += $vh->shoot;   //投篮数
                            $home_s2hit += $vh->shootHit;   //投篮命中数
                            $home_s3num += $vh->threePointShoot;   //三分数
                            $home_s3hit += $vh->threePointHit;   //三分命中数
                            $home_sbnum += $vh->freeThrowShoot;   //罚球数
                            $home_sbhit += $vh->freeThrowHit;   //罚球命中数
                            $home_ords += $vh->offensiveRebound;   //进攻篮板数
                            $home_drds += $vh->defensiveRebound;   //防守篮板数
                            $home_assists += $vh->assist;   //助攻数
                            $home_fouls += $vh->foul;   //犯规数
                            $home_steals += $vh->steal;   //抢断数
                            $home_turnovers += $vh->turnover;   //失误数
                            $home_tbshots += $vh->block;   //盖帽数

                        }

                        Bstatisplayer::updateOrCreate($whereup,$home_attr);
                    }

                    $in_teamstatic_map = [
                        'match_id' => $match_id,
                        'home_name' => $val->homeTeamCn,
                        'guest_name' => $val->awayTeamCn,
                        'home_id' => $home_id,
                        'guest_id' => $guest_id,
                        'home_scoring' => $val->homeScore,
                        'guest_scoring' => $val->awayScore,
                        'home_s2num' => $home_s2num,
                        'home_s2hit' => $home_s2hit,
                        'home_s3num' => $home_s3num,
                        'home_s3hit' => $home_s3hit,
                        'home_sbnum' => $home_sbnum,
                        'home_sbhit' => $home_sbhit,
                        'home_ords' => $home_ords,
                        'home_drds' => $home_drds,
                        'home_assists' => $home_assists,
                        'home_fouls' => $home_fouls,
                        'home_steals' => $home_steals,
                        'home_turnovers' => $home_turnovers,
//                        'home_bshots' => $home_tbshots,
                    ];

                }

                if($guest_players){
                    $guest_s2num = $guest_s2hit = $guest_s3num = $guest_s3hit = $guest_sbnum = $guest_sbhit = $guest_ords = $guest_drds = $guest_assists = $guest_fouls = $guest_steals = $guest_tbshots = $guest_turnovers = 0;
                    foreach($guest_players as $kg => $vg){

                        $whereups = [
                            'match_id' => $match_id,
                            'player_name' => $vg->playerChs
                        ];
                        $guest_attr = [
                            'match_id' => $match_id,
                            'team_id' => $guest_id,
                            'is_host' => 2,
                            'player_id' => $this->save_player($vg->playerId, $vg->playerChs),
                            'out_match_id' => $val->matchId,
                            'out_player_id' => $vg->playerId,
                            'player_name' => $vg->playerChs,
                            'location' => $vg->positionCn,
                            'playtime' => $vg->playtime,
                            'shoot_hit' => $vg->shootHit,
                            'shoot' => $vg->shoot,
                            'threemin_hit' => $vg->threePointHit,
                            'threemin' => $vg->threePointShoot,
                            'punishball_hit' => $vg->freeThrowHit,
                            'punishball' => $vg->freeThrowShoot,
                            'attack' => $vg->offensiveRebound,
                            'defend' => $vg->defensiveRebound,
                            'helpattack' => $vg->assist,
                            'foul' => $vg->foul,
                            'rob' => $vg->steal,
                            'misplay' => $vg->turnover,
                            'cover' => $vg->block,
                            'score' => $vg->score,
                            'is_onfool' => $vg->isOnFloor ? 1 : 0,
                            'source' => self::$source
                        ];
                        Bstatisplayer::updateOrCreate($whereups,$guest_attr);

                        #球队技术统计
                        if ( $vg->isOnFloor ) {
                            $guest_s2num += $vg->shoot;   //投篮数
                            $guest_s2hit += $vg->shootHit;   //投篮命中数
                            $guest_s3num += $vg->threePointShoot;   //三分数
                            $guest_s3hit += $vg->threePointHit;   //三分命中数
                            $guest_sbnum += $vg->freeThrowShoot;   //罚球数
                            $guest_sbhit += $vg->freeThrowHit;   //罚球命中数
                            $guest_ords += $vg->offensiveRebound;   //进攻篮板数
                            $guest_drds += $vg->defensiveRebound;   //防守篮板数
                            $guest_assists += $vg->assist;   //助攻数
                            $guest_fouls += $vg->foul;   //犯规数
                            $guest_steals += $vg->steal;   //抢断数
                            $guest_turnovers += $vg->turnover;   //失误数
                            $guest_tbshots += $vg->block;   //盖帽数

                        }
                    }

                    $in_teamstatic_map['guest_s2num'] = $guest_s2num;
                    $in_teamstatic_map['guest_s2hit'] = $guest_s2hit;
                    $in_teamstatic_map['guest_s3num'] = $guest_s3num;
                    $in_teamstatic_map['guest_s3hit'] = $guest_s3hit;
                    $in_teamstatic_map['guest_sbnum'] = $guest_sbnum;
                    $in_teamstatic_map['guest_sbhit'] = $guest_sbhit;
                    $in_teamstatic_map['guest_ords'] = $guest_ords;
                    $in_teamstatic_map['guest_drds'] = $guest_drds;
                    $in_teamstatic_map['guest_assists'] = $guest_assists;
                    $in_teamstatic_map['guest_fouls'] = $guest_fouls;
                    $in_teamstatic_map['guest_steals'] = $guest_steals;
                    $in_teamstatic_map['guest_turnovers'] = $guest_turnovers;
//                    $in_teamstatic_map['guest_bshots'] = $guest_tbshots;

                    Bstatisteam::updateOrCreate( [ 'match_id' => $in_teamstatic_map['match_id'], 'source' => self::$source ], $in_teamstatic_map );

                }
                DB::commit();
                $this->info('处理完毕');

            } catch (\Exception $e) {
                echo $e->getMessage();exit;
                $this->info($e->getMessage());
                DB::rollBack();
            }
        }
    }

    public function save_team($team_name) {
        return Bteam::firstOrCreate(['shortname' => $team_name])->id;
    }

    public function save_player($out_player_id, $player_name) {
        return Bplayer::firstOrCreate(['out_player_id' => $out_player_id, 'player_name' => $player_name])->id;
    }

    public static function check_process_num($script_name) {
        $cmd = @popen("ps -ef | grep '{$script_name}' | grep -v grep | wc -l", 'r');
        $num = @fread($cmd, 512);
        $num += 0;
        @pclose($cmd);
        if ($num > 1) {
            return false;
        }
        return true;
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