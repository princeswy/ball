<?php

namespace App\Console\Commands;

use App\models\Bmatch;
use App\models\Bmatchlineup;
use App\models\Bplayer;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use App\lib\Xml;

class lqLineUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grab:qtlqLineup {--match_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'qtlqLineup Grab';

    public static $url = "http://interface.win007.com/basketball/lineup.aspx";
    public static $source = 'win007';

    public function handle()
    {
        $match_id = $this->option('match_id') ? $this->option('match_id') : '';
        $url = self::$url;
        if ($match_id) {
            $map = Bmatch::where(['id' => $match_id])->first();
            $map && $out_matchid = $map->out_match_id;

            if ($match_id && !$out_matchid) {
                $this->info('内部赛事ID--' . $match_id . '对应的外部赛事ID不存在');
                return false;
            }
            $url = $url.'?matchId='.$out_matchid;
        }
        $res = self::send_request($url);
        $resData = json_decode($res['content']);

        if(!isset($resData->lineupList) || count($resData->lineupList) === 0){
            $this->info('暂无数据处理');
            return false;
        }

        $out_match_id_map = array_column($resData->lineupList, 'matchId');
        $match_id_map = [];
        $match_map = Bmatch::whereIn('out_match_id', $out_match_id_map)->get(['id', 'out_match_id']);
        if ($match_map) {
            $match_id_map = array_column($match_map->toArray(), 'id', 'out_match_id');
        }

        if(count($match_id_map) == 0){
            return false;
        }

        foreach($resData->lineupList as $km => $vm){
            $match_id = isset($match_id_map[$vm->matchId]) ? $match_id_map[$vm->matchId] : 0;
//            $home_lineup = self::resultDatas($vm->homeLineup,'lineup');
//            $home_backup = self::resultDatas($vm->homeBackup,'lineup');
//            $guest_lineup = self::resultDatas($vm->awayLineup,'lineup');
//            $guest_backup = self::resultDatas($vm->awayBackup,'lineup');
//            $injury = self::resultDatas($vm['INJURY']['value'],'injury');

            $str = '外部赛事ID--'.$vm->matchId.',内部赛事ID--'.$match_id;
            //主队首发
            $hl = self::datasOperate($vm->homeLineup,'hl',$match_id, $vm->matchId);
            if ($hl){
                $str .= '主队首发阵容、';
            }

            //主队后备
            $hb = self::datasOperate($vm->homeBackup,'hb',$match_id, $vm->matchId);
            if($hb){
                $str .= '主队后备阵容、';
            }

            //客队首发
            $gl = self::datasOperate($vm->awayLineup,'gl',$match_id, $vm->matchId);
            if($gl){
                $str .= '客队首发阵容、';
            }

            //客队后备
            $gb = self::datasOperate($vm->awayBackup,'gb',$match_id, $vm->matchId);
            if($gb){
                $str .= '客队后备阵容';
            }
            /*if($injury){
                //球员伤停
                foreach($injury as $ki => $vi){
                    $whereup = [
                        'match_id' => $match_id,
                        'team_name' => $vi['team_name'],
                        'player_name' => $vi['player_name'],
                    ];
                    $is_have = Bmatchinjury::where($whereup)->get()->toArray();
                    if($is_have){
                        continue;
                    }else{
                        $attr = [
                            'match_id' => $match_id,
                            'out_matchid' => $vm['ID']['value'],
                            'team_name' => $vi['team_name'],
                            'player_name' => $vi['player_name'],
                            'position' => $vi['position'],
                            'reason' => $vi['reason'],
                            'miss_date' => $vi['miss_data'],
                            'intro' => $vi['intro'],
                            'source' => self::$source,
                        ];
                        Bmatchinjury::updateOrCreate($whereup, $attr);
                    }
                }
                $str .= '、伤停信息';
            }*/
            $str .= '处理完成';
            $this->info($str);

        }
    }

    private function resultDatas($data,$type){
        if (!$data || count($data) == 0) {
            return false;
        }
        $td_array = [];
        if($data){

            if($type == 'lineup'){
                foreach($data as $k => &$v){
                    $td_array[] = [

                    ];
                    $v['number'] = $v[0];
                    $v['position'] = $v[1];
                    $v['player_name'] = $v[2];
                    unset($v[0],$v[1],$v[2]);
                }
            }else{
                foreach($td_array as $kk => &$vv){
                    unset($td_array[0]);
                    $vv['team_name'] = $vv[0];
                    $vv['player_name'] = $vv[1];
                    $vv['position'] = $vv[2];
                    $vv['reason'] = $vv[3];
                    $vv['miss_data'] = str_replace('/','-',$vv[4]);
                    $vv['intro'] = $vv[5];
                    unset($vv[0],$vv[1],$vv[2],$vv[3],$vv[4],$vv[5]);
                }
                $td_array = array_values($td_array);

            }
        }

        return $td_array;
    }

    private function datasOperate($datas, $type, $match_id, $out_matchid){
        if (!$datas || count($datas) == 0) {
            return false;
        }
        $ret = [];
        if ($datas){
            foreach($datas as $kh => $vh){
                $player_data = [
                    'out_player_id' => $vh->playerId,
                    'player_name' => $vh->nameChs,
                    'short' => $vh->nameChs,
                    'player_name_en' => $vh->nameEn,
                    'player_name_hk' => $vh->nameCht,
                    'number' => $vh->number,
                ];
                $player_id = $this->save_player($player_data);
                $where = [
                    'match_id' => $match_id,
                    'player_id' => $player_id,
                    'source' => self::$source,
                ];
                $dataAttr = [
                    'match_id' => $match_id,
                    'out_matchid' => $out_matchid,
                    'player_name' => $vh->nameChs,
                    'player_id' => $player_id,
                    'out_player_id' => $vh->playerId,
                    'position' => $vh->positionCn,
                    'number' => $vh->number,
                    'source' => self::$source,
                ];
                if ($type == 'hl'){//主队首发
                    $where['is_host'] = 1;
                    $dataAttr['is_host'] = 1;
                    $dataAttr['is_sf'] = 1;

                }elseif ($type == 'hb'){
                    $where['is_host'] = 1;
                    $dataAttr['is_host'] = 1;
                    $dataAttr['is_sf'] = 2;

                }elseif ($type == 'gl'){
                    $where['is_host'] = 2;
                    $dataAttr['is_host'] = 2;
                    $dataAttr['is_sf'] = 1;

                }elseif ($type == 'gb'){
                    $where['is_host'] = 2;
                    $dataAttr['is_host'] = 2;
                    $dataAttr['is_sf'] = 2;
                }
                $re = Bmatchlineup::updateOrCreate($where,$dataAttr);
                $ret[] = $re;
            }
        }

        return $ret;
    }

    public function save_player($data) {
        return Bplayer::firstOrCreate($data)->id;
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
