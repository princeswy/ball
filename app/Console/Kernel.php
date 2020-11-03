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
        \App\Console\Commands\leagueTask::class, // 每天凌晨3点半执行
        // 赛季、阶段、分组、轮次
        \App\Console\Commands\sectionTask::class, // 每天凌晨4点半执行
        //裁判
        \App\Console\Commands\refereeTask::class, // 每天凌晨3点执行
        // 球队
        \App\Console\Commands\teamTask::class, // 每天凌晨4点执行
        // 球员
        \App\Console\Commands\playerTask::class, // 每天凌晨5点执行
        // 比赛
        \App\Console\Commands\matchTask::class, //每十分钟一次
        // 当天比赛数据
        \App\Console\Commands\todayMatchTask::class, // 2分钟一次
        // 球员伤停
        \App\Console\Commands\missPlayerTask::class, // 2分钟一次
        // 阵容
        \App\Console\Commands\lineUpTask::class, // 2分钟一次
        // 积分榜
        \App\Console\Commands\scoreTableTask::class, // 4小时一次
        // 射手榜
        \App\Console\Commands\shootersTask::class, // 每天凌晨5点执行
        // 球员详细技术统计榜
        \App\Console\Commands\playerCountTask::class, // 10分钟一次
        // 欧赔
        \App\Console\Commands\oddsTask::class,
        // 亚盘 1亚盘 2大小球
        \App\Console\Commands\hOddsTask::class,
        \App\Console\Commands\matchDetailTask::class,
        \App\Console\Commands\FScoreLive::class,
        \App\Console\Commands\fLiveScore::class,

        // 篮球
        \App\Console\Commands\lqInitTask::class,
        \App\Console\Commands\lqCrontab::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //足球
        # 球探联赛数据
        $schedule->command('fmatch:leagueTask')->dailyAt('3:30')->runInBackground();
        # 赛事阶段
        $schedule->command('fmatch:sectionTask')->dailyAt('4:00')->runInBackground();
        # 裁判
        $schedule->command('fmatch:refereeTask')->dailyAt('3:00')->runInBackground();
        # 球队
        $schedule->command('fmatch:teamTask')->dailyAt('4:00')->runInBackground();
        # 球员
        $schedule->command('fmatch:playerTask')->dailyAt('5:00')->runInBackground();
        # 未来N天的比赛
        $schedule->command('fmatch:matchTask --day=5')->everyTenMinutes()->runInBackground();
        # 当天比赛数据
        $schedule->command('fmatch:todayMatchTask')->cron('*/1 * * * *')->runInBackground();
        # 球员伤停
        $schedule->command('fmatch:missPlayerTask')->cron('*/2 * * * *')->runInBackground();
        # 阵容
        $schedule->command('fmatch:lineUpTask')->cron('*/2 * * * *')->runInBackground();
        # 积分榜
        $schedule->command('fmatch:scoreTableTask')->cron('0  */4  *  *  *')->runInBackground();
        # 射手榜
        $schedule->command('fmatch:shootersTask')->dailyAt('5:00')->runInBackground();
        # 球员详细技术统计榜
        $schedule->command('fmatch:playerCountTask')->everyTenMinutes()->runInBackground();
        # 欧赔
        $schedule->command('fmatch:oddsTask')->cron('*/3 * * * *')->runInBackground();
        $schedule->command('fmatch:oddsTask')->cron('8,38 * * * *')->runInBackground();
        # 亚盘
        $schedule->command('fmatch:hOddsTask --odds_type=1')->everyThirtyMinutes()->runInBackground();
        # 大小球
        $schedule->command('fmatch:hOddsTask --odds_type=2')->everyThirtyMinutes()->runInBackground();
        # 比赛技术统计
        $schedule->command('fmatch:matchDetail')->everyMinute()->runInBackground();
        # 欧赔
        $schedule->command('fmatch:liveScore')->cron('*/2 * * * *')->runInBackground();

        ####篮球####
        #抓取球探篮球联赛
        $schedule->command('qtlq:init --type=league')->dailyAt('01:00');
        #抓取球探篮球球员
        $schedule->command('qtlq:init --type=player')->dailyAt('01:00');
        #抓取球探篮球球队
        $schedule->command('qtlq:init --type=team')->dailyAt('01:00');
        $schedule->command('lq:crontab --type=odds')->everyThirtyMinutes()->runInBackground();
    }
}
