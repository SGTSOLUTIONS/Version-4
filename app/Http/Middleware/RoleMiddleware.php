<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Enums\RoleEnum;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!in_array(Auth::user()->role, $roles)) {
            // Redirect to their own dashboard instead of 403
            return redirect()->to($this->getDashboardRoute(Auth::user()->role));
        }

        return $next($request);
    }

    private function getDashboardRoute(string $role): string
    {
        return match($role) {
            RoleEnum::ADMIN->value        => route('admin.dashboard'),
            RoleEnum::COMMISSIONER->value => route('commissioner.dashboard'),
            RoleEnum::DC->value           => route('dc.dashboard'),
            RoleEnum::AC->value           => route('ac.dashboard'),
            RoleEnum::ARO->value          => route('aro.dashboard'),
            RoleEnum::BC->value           => route('bc.dashboard'),
            RoleEnum::TEAMLEADER->value   => route('teamleader.dashboard'),
            RoleEnum::SURVEYOR->value     => route('surveyor.dashboard'),
            default                       => route('dashboard'),
        };
    }
}
