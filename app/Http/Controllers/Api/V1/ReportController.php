<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    public function sales(Request $request)
    {
        $warungId = $request->user()->warung_id;
        $period = strtoupper($request->input('period', 'DAILY'));

        $dateFrom = Carbon::today();
        $dateTo = Carbon::today();

        if ($period === 'WEEKLY') {
            $dateFrom = Carbon::now()->startOfWeek();
            $dateTo = Carbon::now()->endOfWeek();
        } elseif ($period === 'MONTHLY') {
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo = Carbon::now()->endOfMonth();
        } elseif ($period === 'CUSTOM') {
            $request->validate([
                'date_from' => 'required|date_format:Y-m-d',
                'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
            ]);
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
        } else {
            // Default DAILY
            $dateFrom = $dateFrom->startOfDay();
            $dateTo = $dateTo->endOfDay();
        }

        // --- Summary & Chart Data ---
        $transactions = DB::table('transactions')
            ->where('warung_id', $warungId)
            ->where('status', 'COMPLETED')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get(['id', 'grand_total', 'payment_method', 'created_at']);

        $totalTransactions = $transactions->count();
        $totalRevenue = $transactions->sum('grand_total');
        $averagePerTransaction = $totalTransactions > 0 ? ($totalRevenue / $totalTransactions) : 0;

        $chartData = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'revenue' => (int) $group->sum('grand_total'),
                'transactions' => $group->count(),
            ];
        })->values()->sortBy('date')->toArray();

        // --- Payment Breakdown ---
        $paymentBreakdown = [
            'CASH' => ['count' => 0, 'amount' => 0],
            'TRANSFER' => ['count' => 0, 'amount' => 0],
            'QRIS' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($transactions as $t) {
            if (isset($paymentBreakdown[$t->payment_method])) {
                $paymentBreakdown[$t->payment_method]['count']++;
                $paymentBreakdown[$t->payment_method]['amount'] += $t->grand_total;
            }
        }

        // --- Top Products ---
        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.warung_id', $warungId)
            ->where('transactions.status', 'COMPLETED')
            ->whereBetween('transactions.created_at', [$dateFrom, $dateTo])
            ->select(
                'transaction_items.product_name',
                DB::raw('SUM(transaction_items.quantity) as quantity_sold'),
                DB::raw('SUM(transaction_items.subtotal) as revenue'),
            )
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        $data = [
            'period' => $period,
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d'),
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_revenue' => (int) $totalRevenue,
                'average_per_transaction' => (int) round($averagePerTransaction),
            ],
            'chart_data' => array_values($chartData),
            'top_products' => $topProducts,
            'payment_breakdown' => $paymentBreakdown,
        ];

        return $this->successResponse($data, 'Laporan penjualan berhasil diambil');
    }

    public function profitLoss(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $warungId = $request->user()->warung_id;
        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();

        // --- Revenue Total ---
        $totalRevenue = DB::table('transactions')
            ->where('warung_id', $warungId)
            ->where('status', 'COMPLETED')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('grand_total') ?? 0;

        // --- Revenue Details (Berdasarkan Kategori Produk) ---
        $revenueDetails = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('transactions.warung_id', $warungId)
            ->where('transactions.status', 'COMPLETED')
            ->whereBetween('transactions.created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('COALESCE(categories.name, "Tanpa Kategori") as category'),
                DB::raw('SUM(transaction_items.subtotal) as amount'),
            )
            ->groupBy(DB::raw('COALESCE(categories.name, "Tanpa Kategori")'))
            ->get();

        // --- Expenses Total ---
        $totalExpenses = DB::table('expenses')
            ->where('warung_id', $warungId)
            ->whereBetween('date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->sum('amount') ?? 0;

        // --- Expenses Details ---
        $expenseDetails = DB::table('expenses')
            ->where('warung_id', $warungId)
            ->whereBetween('date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->select('category', DB::raw('SUM(amount) as amount'))
            ->groupBy('category')
            ->get();

        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $data = [
            'date_from' => $dateFrom->format('Y-m-d'),
            'date_to' => $dateTo->format('Y-m-d'),
            'revenue' => [
                'total' => (int) $totalRevenue,
                'details' => $revenueDetails,
            ],
            'expenses' => [
                'total' => (int) $totalExpenses,
                'details' => $expenseDetails,
            ],
            'net_profit' => (int) $netProfit,
            'profit_margin' => number_format($profitMargin, 2).'%',
        ];

        return $this->successResponse($data, 'Laporan laba rugi berhasil diambil');
    }
}
