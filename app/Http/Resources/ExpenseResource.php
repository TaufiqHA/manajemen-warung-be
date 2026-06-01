<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $months = ['January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'];

        $dateObj = now()->parse($this->date);
        $dayName = $days[$dateObj->format('l')] ?? $dateObj->format('l');
        $monthName = $months[$dateObj->format('F')] ?? $dateObj->format('F');
        $formattedDate = $dayName.', '.$dateObj->format('d').' '.$monthName.' '.$dateObj->format('Y');

        $categoryMap = [
            'OPERATIONAL' => 'Operasional',
            'PURCHASE' => 'Bahan Baku',
            'OTHER' => 'Lainnya',
        ];
        $formattedCategory = $categoryMap[strtoupper($this->category)] ?? $this->category;

        return [
            'id' => 'EXP-'.str_pad($this->id, 3, '0', STR_PAD_LEFT),
            'jumlah' => (float) $this->amount,
            'kategori' => $formattedCategory,
            'catatan' => $this->note ?? '',
            'tanggal' => $formattedDate,

            // Legacy fields for backward compatibility with existing tests
            'title' => $this->title,
            'amount' => $this->amount,
            'category' => $this->category,
            'note' => $this->note,
            'date' => $dateObj->format('Y-m-d'),
            'created_by' => $this->created_by,
        ];
    }
}
