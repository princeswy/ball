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
        // 射手榜
        \App\Console\Commands\ShootersTask::class, // 每天凌晨5点执行
        // 球员详细技术统计榜
        \App\Console\Commands\PlayerCountTask::class, // 10分钟一次
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
        $schedule->command('fmatch:leagueTask')->dailyAt('3:30')->withoutOverlapping();
        # 赛事阶段
        $schedule->command('fmatch:sectionTask')->dailyAt('4:00')->withoutOverlapping();
        # 裁判
        $schedule->command('fmatch:refereeTask')->dailyAt('3:00')->withoutOverlapping();
        # 球队
        $schedule->command('fmatch:teamTask')->dailyAt('4:00')->withoutOverlapping();
        # 球员
        $schedule->command('fmatch:playerTask')->dailyAt('5:00')->withoutOverlapping();
        # 未来N天的比赛
        $schedule->command('fmatch:matchTask --day=5')->everyTenMinutes()->withoutOverlapping();
        # 当天比赛数据
        $schedule->command('fmatch:todayMatchTask')->cron('*/2 * * * *')->withoutOverlapping();
        # 球员伤停
        $schedule->command('fmatch:missPlayerTask')->cron('*/2 * * * *')->withoutOverlapping();
        # 阵容
        $schedule->command('fmatch:lineUpTask')->cron('*/2 * * * *')->withoutOverlapping();
        # 积分榜
        $schedule->command('fmatch:scoreTableTask')->cron('0  */4  *  *  *')->withoutOverlapping();
        # 射手榜
        $schedule->command('fmatch:shootersTask')->dailyAt('5:00')->withoutOverlapping();
        # 球员详细技术统计榜
        $schedule->command('fmatch:playerCountTask')->everyTenMinutes()->withoutOverlapping();
    }
}
