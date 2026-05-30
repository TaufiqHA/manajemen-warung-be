<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use \App\Traits\ApiResponse;

    public function index(Request $request)
    {
        $warungId = $request->user()->warung_id;
        $date = $request->input('date', now()->format('Y-m-d'));
        $parsedDate = Carbon::parse($date);

        $month = $parsedDate->month;
        $year = $parsedDate->year;

        // --- 1. Ringkasan Hari Ini (Today) ---
        $todayStats = DB::table('transactions')
            ->where('warung_id', $warungId)
            ->where('status', 'COMPLETED')
            ->whereDate('created_at', $parsedDate->format('Y-m-d'))
            ->selectRaw('COUNT(id) as total_transactions, SUM(grand_total) as total_revenue')
            ->first();

        $todayExpenses = DB::table('expenses')
            ->where('warung_id', $warungId)
            ->whereDate('date', $parsedDate->format('Y-m-d'))
            ->sum('amount') ?? 0;

        $todayRevenue = $todayStats->total_revenue ?? 0;
        $todayNetProfit = $todayRevenue - $todayExpenses;

        // --- 2. Ringkasan Bulan Berjalan (This Month) ---
        $monthStats = DB::table('transactions')
            ->where('warung_id', $warungId)
            ->where('status', 'COMPLETED')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->selectRaw('COUNT(id) as total_transactions, SUM(grand_total) as total_revenue')
            ->first();

        $monthExpenses = DB::table('expenses')
            ->where('warung_id', $warungId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount') ?? 0;

        $monthRevenue = $monthStats->total_revenue ?? 0;
        $monthNetProfit = $monthRevenue - $monthExpenses;

        // --- 3. Top Products (Hari Ini) ---
        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.warung_id', $warungId)
            ->where('transactions.status', 'COMPLETED')
            ->whereDate('transactions.created_at', $parsedDate->format('Y-m-d'))
            ->select(
                'transaction_items.product_id',
                'transaction_items.product_name',
                DB::raw('SUM(transaction_items.quantity) as total_sold'),
                DB::raw('SUM(transaction_items.subtotal) as total_revenue'),
            )
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // --- 4. Hourly Sales (Hari Ini) ---
        $hourlyTransactions = DB::table('transactions')
            ->where('warung_id', $warungId)
            ->where('status', 'COMPLETED')
            ->whereDate('created_at', $parsedDate->format('Y-m-d'))
            ->get(['created_at', 'grand_total']);

        $hourlySales = $hourlyTransactions->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('H:00');
        })->map(function ($group, $hour) {
            return [
                'hour' => $hour,
                'total' => (int) $group->sum('grand_total'),
            ];
        })->values()->sortBy('hour')->toArray();

        $data = [
            'today' => [
                'total_transactions' => (int) ($todayStats->total_transactions ?? 0),
                'total_revenue' => (int) $todayRevenue,
                'total_expenses' => (int) $todayExpenses,
                'net_profit' => (int) $todayNetProfit,
            ],
            'this_month' => [
                'total_transactions' => (int) ($monthStats->total_transactions ?? 0),
                'total_revenue' => (int) $monthRevenue,
                'total_expenses' => (int) $monthExpenses,
                'net_profit' => (int) $monthNetProfit,
            ],
            'top_products' => $topProducts,
            'hourly_sales' => array_values($hourlySales),
        ];

        return $this->successResponse($data, 'Data dashboard berhasil diambil');
    }
}
