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
        \App\Console\Commands\lqStatistics::class,
        \App\Console\Commands\lqLineUp::class,
        \App\Console\Commands\lqLiveScore::class,
        \App\Console\Commands\bLiveScore::class,
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
        $schedule->command('fmatch:leagueTask')->dailyAt('3:30')->runInBackground()->withoutOverlapping()->withoutOverlapping();
        # 赛事阶段
        $schedule->command('fmatch:sectionTask')->dailyAt('4:00')->runInBackground()->withoutOverlapping();
        # 裁判
        $schedule->command('fmatch:refereeTask')->dailyAt('3:00')->runInBackground()->withoutOverlapping();
        # 球队
        $schedule->command('fmatch:teamTask')->dailyAt('4:00')->runInBackground()->withoutOverlapping();
        # 球员
        $schedule->command('fmatch:playerTask')->dailyAt('5:00')->runInBackground()->withoutOverlapping();
        # 未来N天的比赛
        $schedule->command('fmatch:matchTask --day=5')->cron('0 */2 * * *')->runInBackground()->withoutOverlapping();
        # 当天比赛数据
        $schedule->command('fmatch:todayMatchTask')->cron('*/1 * * * *')->runInBackground()->withoutOverlapping();
        # 球员伤停
        $schedule->command('fmatch:missPlayerTask')->cron('*/2 * * * *')->runInBackground()->withoutOverlapping();
        # 阵容
        $schedule->command('fmatch:lineUpTask')->cron('*/2 * * * *')->runInBackground()->withoutOverlapping();
        # 积分榜
        $schedule->command('fmatch:scoreTableTask')->cron('0  */4  *  *  *')->runInBackground()->withoutOverlapping();
        # 射手榜
        $schedule->command('fmatch:shootersTask')->dailyAt('5:00')->runInBackground()->withoutOverlapping();
        # 球员详细技术统计榜
        $schedule->command('fmatch:playerCountTask')->cron('*/30 * * * *')->runInBackground()->withoutOverlapping();
        # 欧赔
        $schedule->command('fmatch:oddsTask')->cron('*/3 * * * *')->runInBackground()->withoutOverlapping();
        $schedule->command('fmatch:oddsTask')->cron('8,38 * * * *')->runInBackground()->withoutOverlapping();
        # 亚盘
        $schedule->command('fmatch:hOddsTask --odds_type=1')->everyThirtyMinutes()->runInBackground()->withoutOverlapping();
        # 大小球
        $schedule->command('fmatch:hOddsTask --odds_type=2')->everyThirtyMinutes()->runInBackground()->withoutOverlapping();
        # 比赛技术统计
        $schedule->command('fmatch:matchDetail')->everyMinute()->runInBackground()->withoutOverlapping();
        # 欧赔
        $schedule->command('fmatch:liveScore')->cron('*/2 * * * *')->runInBackground()->withoutOverlapping();

        ####篮球####
        #抓取球探篮球联赛
        $schedule->command('lq:init --type=league')->cron('*/60 * * * *')->runInBackground()->withoutOverlapping();
        #抓取球探篮球球员
        $schedule->command('lq:init --type=player')->cron('*/60 * * * *')->runInBackground()->withoutOverlapping();
        #抓取球探篮球球队
        $schedule->command('lq:init --type=team')->cron('*/60 * * * *')->runInBackground()->withoutOverlapping();

        $schedule->command('lq:crontab --type=team')->cron('*/60 * * * *')->runInBackground()->withoutOverlapping();

        $schedule->command('lq:crontab --type=match --days=5')->cron('0 */2 * * *')->runInBackground()->withoutOverlapping();

        $schedule->command('lq:crontab --type=3w_odds')->cron('*/3 * * * *');
        $schedule->command('lq:crontab --type=3w_odds --time=4')->cron('8,38 * * * *');
        $schedule->command('lq:crontab --type=odds --odds_type=3W')->cron('*/3 * * * *');
        $schedule->command('lq:crontab --type=odds --odds_type=HC')->cron('*/4 * * * *');
        $schedule->command('lq:crontab --type=odds --odds_type=asian_total')->cron('*/5 * * * *');

        #抓取球探篮球技术统计
        $schedule->command('lq:lqStatistics')->everyTenMinutes()->runInBackground()->withoutOverlapping();
        #篮球阵容
        $schedule->command('grab:qtlqLineup')->dailyAt('2:00')->runInBackground()->withoutOverlapping();
        #删除、修改比赛
        $schedule->command('lq:crontab --type=modify_match')->everyTenMinutes();
        #当日比分
        $schedule->command('lq:crontab --type=today_score')->everyTenMinutes()->runInBackground()->withoutOverlapping();
        #篮球比分直播
        $schedule->command('grab:event')->everyMinute()->runInBackground()->withoutOverlapping();
    }
}
