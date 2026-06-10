<?php

namespace App\Http\Middleware;

use App\Services\MaxAutoPoster;
use App\Services\ScheduledPostPublisher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublishScheduledPosts
{
    public function __construct(
        private readonly ScheduledPostPublisher $scheduledPostPublisher,
        private readonly MaxAutoPoster $maxAutoPoster
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethodCacheable()
            && ! $request->is('notaadmin*')
            && ! $request->is('api/*')
        ) {
            $this->scheduledPostPublisher->publishDuePosts();
            $this->maxAutoPoster->postLatestIfDue();
        }

        return $next($request);
    }
}
