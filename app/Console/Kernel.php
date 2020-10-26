<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        // 联赛
        \App\Console\Commands\LeagueTask::class, // 每天凌晨3点半执行
        // 赛季、阶段、分组、轮次
        \App\Console\Commands\SectionTask::class, // 每天凌晨4点半执行
        //裁判
        \App\Console\Commands\RefereeTask::class, // 每天凌晨3点执行
        // 球队
        \App\Console\Commands\TeamTask::class, // 每天凌晨4点执行
        // 球员
        \App\Console\Commands\PlayerTask::class, // 每天凌晨5点执行
        // 比赛
        \App\Console\Commands\MatchTask::class, //每十分钟一次
        // 当天比赛数据
        \App\Console\Commands\TodayMatchTask::class, // 2分钟一次
        // 球员伤停
        \App\Console\Commands\MissPlayerTask::class, // 2分钟一次
        // 阵容
        \App\Console\Commands\LineUpTask::class, // 2分钟一次
        // 积分榜
        \App\Console\Commands\ScoreTableTask::class, // 4小时一次
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
