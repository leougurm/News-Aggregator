<?php

use App\Jobs\FetchNewsFromSourceJob;
use App\Jobs\SyncCategoriesFromSourceJob;
use App\Models\ArticleSource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;


Artisan::command('startup-fetch:categories', function () {
    sleep(5);
    \App\Models\ArticleSource::get()->each(function (ArticleSource $articleSource) {
        Log::info('source', ['source' => $articleSource->name]);
        SyncCategoriesFromSourceJob::dispatchSync($articleSource->name);
    });
})->purpose('Startup function');

Artisan::command('startup-fetch:news', function () {
    \App\Models\ArticleSource::get()->each(function (ArticleSource $articleSource) {
        $this->comment("Syncing articles from sources");
        FetchNewsFromSourceJob::dispatch($articleSource->name)->onQueue('news-fetch');
    });
})->purpose('Startup function');

Schedule::call(function () {
    \App\Models\ArticleSource::get()->each(function (ArticleSource $articleSource) {
        Log::info("Scheduler: Dispatching $articleSource->name fetch job");
        FetchNewsFromSourceJob::dispatch($articleSource->name)->onQueue('news-fetch');
        Log::info("Scheduler: $articleSource->name job dispatched to queue");
    });
})
    ->everyFifteenMinutes()
    ->name('fetch-newsapi')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::call(function () {
    \App\Models\ArticleSource::get()->each(function (ArticleSource $articleSource) {
        Log::info("Scheduler: Dispatching $articleSource->name fetch categories");
        SyncCategoriesFromSourceJob::dispatch($articleSource->name)->onQueue('news-fetch');
        Log::info("Categories Scheduler: $articleSource->name job dispatched to queue");
    });
})
    ->daily()
    ->name('fetch-news')
    ->withoutOverlapping()
    ->onOneServer();


