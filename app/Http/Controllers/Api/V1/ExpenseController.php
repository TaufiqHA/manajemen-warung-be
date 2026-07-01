<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $filter = $request->input('filter', 'Semua');
        $query = Expense::where('warung_id', $user->warung_id);

        if ($filter === 'Hari Ini') {
            $query->whereDate('date', now()->toDateString());
        } elseif ($filter === 'Minggu Ini') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'Bulan Ini') {
            $query->whereMonth('date', now()->month)
                ->whereYear('date', now()->year);
        } elseif ($filter === 'Bulan Lalu') {
            $lastMonth = now()->subMonth();
            $query->whereMonth('date', $lastMonth->month)
                ->whereYear('date', $lastMonth->year);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ExpenseResource::collection($expenses),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'EXP-')) {
            $id = (int) substr($id, 4);
        }

        $expense = Expense::where('warung_id', $user->warung_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $user = $request->user();

        $amount = $request->input('jumlah') ?? $request->input('amount');

        $kategoriInput = $request->input('kategori') ?? $request->input('category') ?? 'LAINNYA';
        $categoryMap = [
            'BAHAN BAKU' => 'BAHAN_BAKU',
            'BAHAN_BAKU' => 'BAHAN_BAKU',
            'BAHAN' => 'BAHAN_BAKU',
            'OPERASIONAL' => 'LAINNYA',
            'GAJI' => 'GAJI',
            'LISTRIK' => 'LISTRIK',
            'AIR' => 'AIR',
            'SEWA' => 'SEWA',
            'PERALATAN' => 'PERALATAN',
            'LAINNYA' => 'LAINNYA',
            'BIAYA DLL' => 'LAINNYA',
            'BIAYA OPERASIONAL' => 'BIAYA_OPERASIONAL',
        ];
        $category = strtoupper($kategoriInput);
        $category = $categoryMap[$category] ?? $kategoriInput;

        $note = $request->input('catatan') ?? $request->input('note') ?? $request->input('keterangan');
        $date = $request->input('tanggal') ?? $request->input('date') ?? now()->format('Y-m-d');
        $parsedDate = $this->parseIndonesianDate($date);

        $expense = Expense::create([
            'warung_id' => $user->warung_id,
            'created_by' => $user->id,
            'title' => $request->input('title') ?? $note ?? 'Pengeluaran',
            'amount' => $amount,
            'category' => $category,
            'note' => $note,
            'date' => $parsedDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    public function update(UpdateExpenseRequest $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'EXP-')) {
            $id = (int) substr($id, 4);
        }

        $expense = Expense::where('warung_id', $user->warung_id)->findOrFail($id);

        $dataToUpdate = [];
        if ($request->has('jumlah') || $request->has('amount')) {
            $dataToUpdate['amount'] = $request->input('jumlah') ?? $request->input('amount');
        }
        if ($request->has('kategori') || $request->has('category')) {
            $kategoriInput = $request->input('kategori') ?? $request->input('category');
            $categoryMap = [
                'BAHAN BAKU' => 'BAHAN_BAKU',
                'BAHAN_BAKU' => 'BAHAN_BAKU',
                'BAHAN' => 'BAHAN_BAKU',
                'OPERASIONAL' => 'LAINNYA',
                'GAJI' => 'GAJI',
                'LISTRIK' => 'LISTRIK',
                'AIR' => 'AIR',
                'SEWA' => 'SEWA',
                'PERALATAN' => 'PERALATAN',
                'LAINNYA' => 'LAINNYA',
                'BIAYA DLL' => 'LAINNYA',
                'BIAYA OPERASIONAL' => 'BIAYA_OPERASIONAL',
            ];
            $category = strtoupper($kategoriInput);
            $dataToUpdate['category'] = $categoryMap[$category] ?? $kategoriInput;
        }
        if ($request->has('catatan') || $request->has('note') || $request->has('keterangan')) {
            $dataToUpdate['note'] = $request->input('catatan') ?? $request->input('note') ?? $request->input('keterangan');
        }
        if ($request->has('title')) {
            $dataToUpdate['title'] = $request->input('title');
        }
        if ($request->has('tanggal') || $request->has('date')) {
            $date = $request->input('tanggal') ?? $request->input('date');
            $parsedDate = $this->parseIndonesianDate($date, false);
            if ($parsedDate !== false) {
                $dataToUpdate['date'] = $parsedDate;
            }
        }

        $expense->update($dataToUpdate);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil diperbarui',
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (is_string($id) && str_starts_with($id, 'EXP-')) {
            $id = (int) substr($id, 4);
        }

        $expense = Expense::where('warung_id', $user->warung_id)->findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus',
        ]);
    }

    /**
     * Parse date string that might contain Indonesian day or month names.
     *
     * @param string|null $date
     * @param mixed $fallback
     * @return string|mixed
     */
    private function parseIndonesianDate($date, $fallback = null)
    {
        if (empty($date)) {
            return $fallback ?? now()->format('Y-m-d');
        }

        // Translasi dari format Indonesia ke Inggris agar Carbon bisa parsing
        $idMonths = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $idDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $enDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $dateTransformed = str_ireplace($idMonths, $enMonths, $date);
        $dateTransformed = str_ireplace($idDays, $enDays, $dateTransformed);

        try {
            return now()->parse($dateTransformed)->format('Y-m-d');
        } catch (\Exception $e) {
            return $fallback ?? now()->format('Y-m-d');
        }
    }
}
