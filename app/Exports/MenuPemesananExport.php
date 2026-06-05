<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Warung;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MenuPemesananExport implements FromView, WithColumnWidths, WithDrawings, WithEvents, WithStyles
{
    protected int $warungId;

    public function __construct(int $warungId)
    {
        $this->warungId = $warungId;
    }

    /**
     * Format price to K format or standard dot format.
     */
    protected function formatPrice(float $price): string
    {
        if ($price >= 1000 && $price % 1000 === 0) {
            return 'Rp. '.($price / 1000).' K';
        } elseif ($price >= 1000 && $price % 100 === 0) {
            return 'Rp. '.number_format($price / 1000, 1, '.', '').' K';
        }

        return 'Rp. '.number_format($price, 0, ',', '.');
    }

    /**
     * Render the Blade view for Excel.
     */
    public function view(): View
    {
        // Get categories with their active products, ordered by name
        $categories = Category::where('warung_id', $this->warungId)
            ->with(['products' => function ($query) {
                $query->where('is_active', true)->orderBy('name', 'asc');
            }])
            ->get()
            ->filter(function ($category) {
                return $category->products->count() > 0;
            })
            ->values();

        // Balanced partitioning logic to divide categories into left and right columns
        $heights = [];
        foreach ($categories as $cat) {
            $heights[] = 1 + $cat->products->count() + 1; // header + products count + 1 spacer row
        }

        $totalHeight = array_sum($heights);
        $targetHalf = $totalHeight / 2;

        $leftCategories = collect();
        $rightCategories = collect();

        $currentLeftHeight = 0;
        foreach ($categories as $index => $cat) {
            $catHeight = $heights[$index];
            // If adding to left keeps it close to target or if left is empty
            if ($currentLeftHeight + $catHeight <= $targetHalf || $leftCategories->isEmpty()) {
                $leftCategories->push($cat);
                $currentLeftHeight += $catHeight;
            } else {
                $rightCategories->push($cat);
            }
        }

        // Flatten rows for left column
        $leftRows = [];
        foreach ($leftCategories as $category) {
            $leftRows[] = [
                'type' => 'header',
                'name' => strtoupper($category->name),
            ];

            $num = 1;
            foreach ($category->products as $product) {
                $leftRows[] = [
                    'type' => 'product',
                    'number' => $num++,
                    'name' => $product->name,
                    'price' => $this->formatPrice($product->price),
                ];
            }

            // Spacer row
            $leftRows[] = [
                'type' => 'spacer',
            ];
        }

        // Flatten rows for right column
        $rightRows = [];
        foreach ($rightCategories as $category) {
            $rightRows[] = [
                'type' => 'header',
                'name' => strtoupper($category->name),
            ];

            $num = 1;
            foreach ($category->products as $product) {
                $rightRows[] = [
                    'type' => 'product',
                    'number' => $num++,
                    'name' => $product->name,
                    'price' => $this->formatPrice($product->price),
                ];
            }

            // Spacer row
            $rightRows[] = [
                'type' => 'spacer',
            ];
        }

        // Zip them together
        $rows = [];
        $maxRows = max(count($leftRows), count($rightRows));
        for ($i = 0; $i < $maxRows; $i++) {
            $rows[] = [
                'left' => $leftRows[$i] ?? ['type' => 'spacer'],
                'right' => $rightRows[$i] ?? ['type' => 'spacer'],
            ];
        }

        return view('exports.menu-pemesanan', [
            'rows' => $rows,
            'warung' => Warung::find($this->warungId),
        ]);
    }

    /**
     * Inject drawing logo.
     */
    public function drawings()
    {
        $warung = Warung::find($this->warungId);
        if ($warung && $warung->logo_url) {
            // Check if logo exists in public folder
            $logoPath = public_path($warung->logo_url);
            if (file_exists($logoPath)) {
                $drawing = new Drawing;
                $drawing->setName('Logo');
                $drawing->setDescription('Logo Warung');
                $drawing->setPath($logoPath);
                $drawing->setHeight(50);
                $drawing->setCoordinates('A1');

                return $drawing;
            }
        }

        // Fallback default logo if any
        $defaultLogo = public_path('images/logo.png');
        if (file_exists($defaultLogo)) {
            $drawing = new Drawing;
            $drawing->setName('Logo');
            $drawing->setDescription('Logo Default');
            $drawing->setPath($defaultLogo);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');

            return $drawing;
        }

        return [];
    }

    /**
     * Column Widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 4,  // Nomor Kiri
            'B' => 35, // Nama Kiri (Panjang)
            'C' => 12, // Harga Kiri
            'D' => 6,  // Kotak Kiri (Bentuk Persegi)
            'E' => 5,  // Spacer Tengah
            'F' => 4,  // Nomor Kanan
            'G' => 35, // Nama Kanan (Panjang)
            'H' => 12, // Harga Kanan
            'I' => 6,  // Kotak Kanan & Kotak MEJA (Bentuk Persegi)
        ];
    }

    /**
     * Set Global sheet styles.
     */
    public function styles(Worksheet $sheet)
    {
        // Set default font to Arial
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->getParent()->getDefaultStyle()->getFont()->setSize(10);
    }

    /**
     * Page setup for printing.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Page setup A4 Portrait
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

                // Fit to page
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setFitToPage(true);

                // Print settings
                $sheet->setPrintGridlines(false);
            },
        ];
    }
}
