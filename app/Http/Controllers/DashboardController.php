<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $today = $this->dashboard->todayStats($user);
        $canSeeFinance = $user->isAdmin() || $user->isGerant();

        return view('dashboard', [
            'today' => $today,
            'canSeeFinance' => $canSeeFinance,
            'topProducts' => $canSeeFinance ? $this->dashboard->topProducts() : collect(),
            'lowStock' => $canSeeFinance ? $this->dashboard->lowStockItems() : collect(),
            'alerts' => $canSeeFinance ? $this->dashboard->alerts($today) : [],
        ]);
    }
}
