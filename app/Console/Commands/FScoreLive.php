<?php

namespace App\Console\Commands;

use App\models\Fevent;
use Illuminate\Console\Command;
use swoole_redis;

class FScoreLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fmatch:socket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '足球比分直播socket';
    private static $server = null;
    public static $match_id = 0;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $server = self::getWebSocketServer();

        /*$process = new \swoole_process([$this,'push_process']);

        self::$server->addProcess($process);*/

        $server->on('open',[$this,'onOpen']);
        $server->on('message', [$this, 'onMessage']);
        $server->on('close', [$this, 'onClose']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('WorkerStart',[$this,'onWorkerStart']);
        $server->on('Finish',[$this,'onFinish']);
        $server->on('task',[$this,'onTask']);
        $this->line("swoole服务启动成功 ...");
        $server->start();
    }

    // 获取服务
    public static function getWebSocketServer()
    {
        if (!(self::$server instanceof \swoole_websocket_server)) {
            self::setWebSocketServer();
        }
        return self::$server;
    }
    // 服务处始设置
    protected static function setWebSocketServer()
    {
        self::$server = new \swoole_websocket_server("0.0.0.0", 9501);
        self::$server->set([
            'worker_num' => 1,
            'task_worker_num' => 2,
            'heartbeat_check_interval' => 60, // 60秒检测一次
            'heartbeat_idle_time' => 121, // 121秒没活动的
        ]);
    }

    // 打开swoole websocket服务回调代码
    public function onOpen($server, $request)
    {
        if ($this->checkAccess($server, $request)) {
//            self::$match_id = $request->get['match_id'];
//            dd($request->get['match_id']);
            self::$server->push($request->fd,"打开swoole服务成功！");
        }
    }
    public function onWorkerStart($server, $result) {
        $server->tick(1000, function () use ($server) {
            if(!$server->worker_id){
                $server->task('push');
            }
        });
        /*$server->tick(3000, function () use ($server, $result) {
            var_dump($result[0]);
            foreach ($server->connections  as $fd){
                $rand = mt_rand(0,9999);
                $server->push($fd, self::$match_id);
            }
        });*/
    }
    // 给swoole websocket 发送消息回调代码
    public function onMessage($server, $frame)
    {
        $server->push($frame->fd, date('Y-m-d H:i:s').$frame->fd);
    }

    public function onTask($server, $task_id, $from_id, $data) {
        $data = Fevent::get_score();
        if($data && $server->connections){
            foreach($server->connections as $fd) {
                if($server->connection_info($fd)['websocket_status'] == 3){
                    $server->push($fd, $data);
                }
            }
        }
    }

    public function onRequest($request, $response)
    {
        $scene = $request->post['scene'];       // 获取值
        $this->info("client is PushMessage\n".$scene);
    }

    // websocket 关闭回调代码
    public function onClose($serv,$fd)
    {
        $this->line("客户端 {$fd} 关闭");
    }
    // 校验客户端连接的合法性,无效的连接不允许连接
    public function checkAccess($server, $request)
    {
        $bRes = true;
        /*if (!isset($request->get) || !isset($request->get['token'])) {
            self::$server->close($request->fd);
            $this->line("接口验证字段不全");
            $bRes = false;
        } else if ($request->get['token'] !== "123456") {
            $this->line("接口验证错误");
            $bRes = false;
        }*/
        return $bRes;
    }
    // 启动websocket服务
    public function start()
    {
        self::$server->start();
    }

    public function onFinish($server, $task_id, $reponse)
    {
        // 	    echo "Task: {$task_id} success {$reponse}\n";
    }
}