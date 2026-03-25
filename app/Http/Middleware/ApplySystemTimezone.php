<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $tz = SystemSetting::getTimezone();
        date_default_timezone_set($tz);
        Carbon::setLocale('es');

        return $next($request);
    }
}
