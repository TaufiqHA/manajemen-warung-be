<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $filter = $request->input('filter', 'Semua');
        $query = Transaction::with(['cashier', 'items'])
            ->where('warung_id', $user->warung_id);

        if ($filter === 'Hari Ini') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($filter === 'Minggu Ini') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'Bulan Ini') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($filter === 'Bulan Lalu') {
            $lastMonth = now()->subMonthNoOverflow();
            $query->whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $flatItems = [];
        foreach ($transactions as $transaction) {
            $isCancelled = $transaction->status === 'CANCELLED';
            foreach ($transaction->items as $item) {
                $flatItems[] = [
                    'idTransaksi' => $transaction->transaction_code,
                    'id' => 'PRD-'.str_pad($item->product_id, 3, '0', STR_PAD_LEFT),
                    'namaItem' => $isCancelled ? "❌ [BATAL] {$item->product_name}" : $item->product_name,
                    'jumlah' => (int) $item->quantity,
                    'harga' => $isCancelled ? 0.0 : (float) $item->unit_price,
                    'waktu' => $transaction->created_at->toISOString(),
                    'dicatatOleh' => $transaction->cashier ? $transaction->cashier->name : 'System',
                    'catatan' => $item->catatan ?? '',
                    'servedQty' => (int) $item->served_qty,
                    'customerName' => $transaction->customer_name,
                    'orderStatus' => $transaction->status,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $flatItems,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaction::with(['cashier', 'items'])
            ->where('warung_id', $user->warung_id)
            ->findOrFail($id);

        return $this->successResponse(new TransactionResource($transaction), 'Detail transaksi berhasil diambil');
    }

    public function store(StoreTransactionRequest $request)
    {
        $user = $request->user();
        $warung = $user->warung;
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $transactionItemsData = [];

            foreach ($validated['items'] as $itemData) {
                // Find product by product_id OR namaItem
                $product = null;
                $productId = $itemData['product_id'] ?? null;
                if (!empty($productId) && is_string($productId) && str_starts_with($productId, 'PRD-')) {
                    $productId = (int) substr($productId, 4);
                }

                if (! empty($productId)) {
                    $product = Product::where('id', $productId)
                        ->where('warung_id', $user->warung_id)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $product && ! empty($itemData['namaItem'])) {
                    $product = Product::where('name', $itemData['namaItem'])
                        ->where('warung_id', $user->warung_id)
                        ->lockForUpdate()
                        ->first();
                }

                // Fallback / Auto-create for custom items from api-reference
                if (! $product) {
                    $productName = $itemData['namaItem'] ?? $itemData['product_name'] ?? 'Unknown Item';
                    $productPrice = $itemData['harga'] ?? $itemData['unit_price'] ?? 5000;
                    $product = Product::create([
                        'warung_id' => $user->warung_id,
                        'name' => $productName,
                        'price' => $productPrice,
                        'stock' => 100,
                        'is_active' => true,
                    ]);
                }

                $quantity = $itemData['jumlah'] ?? $itemData['quantity'] ?? 1;

                if ($product->stock < $quantity) {
                    throw new \Exception("Stok produk {$product->name} tidak mencukupi.");
                }

                $unitPrice = $itemData['harga'] ?? $itemData['unit_price'] ?? $product->price;
                $discount = $itemData['discount'] ?? 0;
                $subtotal = ($unitPrice * $quantity) - $discount;
                $totalAmount += $subtotal;

                $transactionItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'served_qty' => $itemData['servedQty'] ?? 0,
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'catatan' => $itemData['catatan'] ?? $itemData['note'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Kurangi stok produk
                $product->decrement('stock', $quantity);
            }

            $discountAmount = $validated['discount_amount'] ?? 0;

            $taxAmount = 0;
            if ($warung->is_tax_enabled ?? false) {
                $taxPercentage = $warung->tax_percentage ?? 0;
                $taxAmount = ($totalAmount * $taxPercentage) / 100;
            }

            $grandTotal = $totalAmount - $discountAmount + $taxAmount;
            $paidAmount = $validated['paid_amount'] ?? $grandTotal;

            if ($paidAmount < $grandTotal) {
                throw new \Exception('Uang bayar kurang dari total belanja.');
            }

            $changeAmount = $paidAmount - $grandTotal;

            // Generate Transaction Code
            if (! empty($validated['idTransaksi'])) {
                $transactionCode = $validated['idTransaksi'];
            } else {
                $today = now()->format('Ymd');
                $countToday = Transaction::whereDate('created_at', now()->toDateString())
                    ->where('warung_id', $user->warung_id)
                    ->lockForUpdate()
                    ->count();
                $sequence = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
                $transactionCode = "TRX-{$today}-{$sequence}";
            }

            $waktu = ! empty($validated['waktu']) ? now()->parse($validated['waktu']) : now();

            $transaction = Transaction::create([
                'warung_id' => $user->warung_id,
                'cashier_id' => $user->id,
                'customer_name' => $validated['customerName'] ?? null,
                'transaction_code' => $transactionCode,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'payment_method' => $validated['payment_method'] ?? 'CASH',
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => $validated['orderStatus'] ?? 'COMPLETED',
                'note' => $validated['note'] ?? null,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);

            foreach ($transactionItemsData as $itemData) {
                $transaction->items()->create($itemData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'idTransaksi' => $transaction->transaction_code,
                    'waktu' => $transaction->created_at->toISOString(),
                    'totalHarga' => (float) $transaction->grand_total,
                    // Backward compatibility fields for existing tests:
                    'grand_total' => (float) $transaction->grand_total,
                    'change_amount' => (float) $transaction->change_amount,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Gagal memproses transaksi.', ['error' => [$e->getMessage()]], 400);
        }
    }

    public function cancel(CancelTransactionRequest $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::with('items')
            ->where('warung_id', $user->warung_id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('transaction_code', $id);
            })
            ->firstOrFail();

        if ($transaction->status !== 'COMPLETED') {
            return $this->errorResponse('Hanya transaksi berstatus COMPLETED yang bisa dibatalkan.', null, 400);
        }

        try {
            DB::beginTransaction();

            // Kembalikan stok
            foreach ($transaction->items as $item) {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }

            $transaction->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancel_reason' => $request->reason,
            ]);

            DB::commit();

            Log::info('Transaksi berhasil dibatalkan.', [
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'cancelled_by_user_id' => $user->id,
                'reason' => $request->reason,
            ]);

            return $this->successResponse(new TransactionResource($transaction), 'Transaksi berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal membatalkan transaksi.', [
                'transaction_id_or_code' => $id,
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
            ]);

            return $this->errorResponse('Gagal membatalkan transaksi.', ['error' => [$e->getMessage()]], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        $request->validate([
            'status' => 'required|string',
        ]);

        $transaction = Transaction::where('warung_id', $user->warung_id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('transaction_code', $id);
            })
            ->firstOrFail();

        $transaction->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi berhasil diperbarui',
            'data' => [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'status' => $transaction->status,
            ]
        ], 200);
    }

    public function addItems(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('warung_id', $user->warung_id)
                ->where(function ($query) use ($id) {
                    $query->where('id', $id)
                        ->orWhere('transaction_code', $id);
                })
                ->lockForUpdate()
                ->firstOrFail();

            $productId = $validated['product_id'];
            if (is_string($productId) && str_starts_with($productId, 'PRD-')) {
                $productId = (int) substr($productId, 4);
            }

            // Find product
            $product = Product::where('id', $productId)
                ->where('warung_id', $user->warung_id)
                ->lockForUpdate()
                ->first();

            $productName = 'Unknown Product';
            if ($product) {
                if ($product->stock < $validated['quantity']) {
                    throw new \Exception("Stok produk {$product->name} tidak mencukupi.");
                }
                $product->decrement('stock', $validated['quantity']);
                $productName = $product->name;
            }

            // Check if item already exists in transaction
            $existingItem = $transaction->items()->where('product_id', $productId)->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $validated['quantity'],
                    'subtotal' => $existingItem->subtotal + $validated['subtotal']
                ]);
            } else {
                $transaction->items()->create([
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'unit_price' => $validated['unit_price'],
                    'quantity' => $validated['quantity'],
                    'served_qty' => 0,
                    'discount' => 0,
                    'subtotal' => $validated['subtotal'],
                ]);
            }

            // Recalculate Transaction Totals
            $transaction->total_amount += $validated['subtotal'];
            
            // Tax Calculation if any
            $taxAmount = 0;
            if (isset($user->warung) && $user->warung->is_tax_enabled) {
                $taxPercentage = $user->warung->tax_percentage ?? 0;
                $taxAmount = ($transaction->total_amount * $taxPercentage) / 100;
            }

            $transaction->tax_amount = $taxAmount;
            $transaction->grand_total = $transaction->total_amount - $transaction->discount_amount + $taxAmount;
            
            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan ke transaksi',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'grand_total' => $transaction->grand_total
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah item ke transaksi.',
                'error' => [$e->getMessage()]
            ], 400);
        }
    }

    public function removeItem(Request $request, $id, $itemId)
    {
        $user = $request->user();

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('warung_id', $user->warung_id)
                ->where(function ($query) use ($id) {
                    $query->where('id', $id)
                        ->orWhere('transaction_code', $id);
                })
                ->lockForUpdate()
                ->firstOrFail();

            if (is_string($itemId) && str_starts_with($itemId, 'PRD-')) {
                $itemId = (int) substr($itemId, 4);
            }

            $item = $transaction->items()->where('product_id', $itemId)->firstOrFail();
            
            // Add stock back
            $product = Product::where('id', $itemId)
                ->where('warung_id', $user->warung_id)
                ->first();
            if ($product) {
                $product->increment('stock', $item->quantity);
            }

            // Recalculate Transaction Totals
            $transaction->total_amount -= $item->subtotal;
            
            // Tax Calculation if any
            $taxAmount = 0;
            if (isset($user->warung) && $user->warung->is_tax_enabled) {
                $taxPercentage = $user->warung->tax_percentage ?? 0;
                $taxAmount = ($transaction->total_amount * $taxPercentage) / 100;
            }

            $transaction->tax_amount = $taxAmount;
            $transaction->grand_total = $transaction->total_amount - $transaction->discount_amount + $taxAmount;
            
            // If grand_total is 0 or less, we might cancel the transaction, but let the frontend decide.
            if ($transaction->total_amount <= 0) {
                $transaction->status = 'CANCELLED';
            }

            $transaction->save();
            $item->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus dari transaksi',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'grand_total' => $transaction->grand_total,
                    'status' => $transaction->status
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item dari transaksi.',
                'error' => [$e->getMessage()]
            ], 400);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // 1. Cari transaksi milik warung user
        $transaction = Transaction::where('warung_id', $user->warung_id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('transaction_code', $id);
            })
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // 2. Hapus detail item (jika relasi di database tidak memakai on delete cascade)
            $transaction->items()->delete();

            // 3. Hapus data transaksi utama
            $transaction->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data transaksi berhasil dihapus secara permanen',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi',
                'error' => [$e->getMessage()],
            ], 500);
        }
    }
}
