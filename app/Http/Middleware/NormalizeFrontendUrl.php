<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeFrontendUrl
{
    /**
     * Приводит frontend URL к единому canonical виду:
     * - non-www
     * - без паразитного q, если он дублирует текущий путь
     * - без завершающего slash для не-корневых URL
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $host = $request->getHost();
        $path = '/' . ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        $normalizedHost = preg_replace('/^www\./i', '', $host) ?: $host;
        $normalizedPath = $path !== '/' ? rtrim($path, '/') : '/';

        $query = $request->query();
        if ($this->isDuplicatedPathQuery($query, $normalizedPath)) {
            unset($query['q']);
        }

        $needsRedirect = false;

        if ($normalizedHost !== $host) {
            $needsRedirect = true;
        }

        if ($normalizedPath !== $path) {
            $needsRedirect = true;
        }

        if ($query !== $request->query()) {
            $needsRedirect = true;
        }

        if (!$needsRedirect) {
            return $next($request);
        }

        $target = $request->getScheme() . '://' . $normalizedHost . $normalizedPath;
        if (!empty($query)) {
            $target .= '?' . http_build_query($query);
        }

        return redirect()->to($target, 301);
    }

    protected function shouldSkip(Request $request): bool
    {
        if ($request->is('notaadmin') || $request->is('notaadmin/*')) {
            return true;
        }

        if ($request->is('api') || $request->is('api/*')) {
            return true;
        }

        if ($request->is('up')) {
            return true;
        }

        return false;
    }

    protected function isDuplicatedPathQuery(array $query, string $normalizedPath): bool
    {
        if (!isset($query['q']) || !is_string($query['q'])) {
            return false;
        }

        if ($query['q'] === '') {
            return false;
        }

        $candidate = parse_url($query['q'], PHP_URL_PATH);
        if (!is_string($candidate) || $candidate === '') {
            $candidate = $query['q'];
        }

        $candidate = '/' . ltrim(rawurldecode($candidate), '/');
        $candidate = $candidate !== '/' ? rtrim($candidate, '/') : '/';

        return $candidate === $normalizedPath;
    }
}
