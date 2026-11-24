<?php

namespace App\Services;

use RuntimeException;

class CardLayoutCalculatorService
{
    public function calculate(array $layout, int $totalStudents = 0): array
    {
        $throwOnError = func_num_args() === 1;

        $paperSize = (string) ($layout['print_paper_size'] ?? 'A4');
        $orientation = ($layout['print_orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
        $isDoubleSided = ($layout['card_side_mode'] ?? 'single') === 'double_sided';

        [$baseWidth, $baseHeight] = $this->resolvePaperDimensions($paperSize, $layout);

        $paperWidth = $orientation === 'landscape' ? $baseHeight : $baseWidth;
        $paperHeight = $orientation === 'landscape' ? $baseWidth : $baseHeight;

        $marginTop = max(0.0, (float) ($layout['margin_top_mm'] ?? 10.0));
        $marginRight = max(0.0, (float) ($layout['margin_right_mm'] ?? 10.0));
        $marginBottom = max(0.0, (float) ($layout['margin_bottom_mm'] ?? 10.0));
        $marginLeft = max(0.0, (float) ($layout['margin_left_mm'] ?? 10.0));

        $gapX = max(0.0, (float) ($layout['gap_x_mm'] ?? 4.0));
        $gapY = max(0.0, (float) ($layout['gap_y_mm'] ?? 4.0));

        $cardWidth = max(20.0, (float) ($layout['card_width_mm'] ?? 90.0));
        $cardHeight = max(20.0, (float) ($layout['card_height_mm'] ?? 55.0));

        $printableWidth = round($paperWidth - $marginLeft - $marginRight, 3);
        $printableHeight = round($paperHeight - $marginTop - $marginBottom, 3);

        $unitWidth = $isDoubleSided ? round(($cardWidth * 2.0) + $gapX, 3) : $cardWidth;
        $unitHeight = $cardHeight;

        $errors = [];

        if ($printableWidth <= 0 || $printableHeight <= 0) {
            $errors[] = 'Margin kertas terlalu besar sehingga area cetak habis. Kurangi margin atas, bawah, kiri, atau kanan.';
        }

        $maxCols = ($printableWidth > 0)
            ? (int) floor(($printableWidth + $gapX + 0.001) / ($unitWidth + $gapX))
            : 0;
        $maxRows = ($printableHeight > 0)
            ? (int) floor(($printableHeight + $gapY + 0.001) / ($unitHeight + $gapY))
            : 0;

        if (empty($errors) && ($maxCols < 1 || $maxRows < 1)) {
            if ($isDoubleSided) {
                $errors[] = 'Ukuran pasangan kartu dua sisi (' . number_format($unitWidth, 1) . ' × ' . number_format($unitHeight, 1) .
                    ' mm) melebihi area cetak kertas (' . number_format($printableWidth, 1) . ' × ' . number_format($printableHeight, 1) .
                    ' mm). Kecilkan lebar/tinggi kartu atau kurangi margin kertas.';
            } else {
                $errors[] = 'Ukuran kartu (' . number_format($unitWidth, 1) . ' × ' . number_format($unitHeight, 1) .
                    ' mm) melebihi area cetak kertas (' . number_format($printableWidth, 1) . ' × ' . number_format($printableHeight, 1) . ' mm).';
            }
        }

        $requestedCols = max(0, (int) ($layout['cards_per_row'] ?? 0));
        $requestedRows = max(0, (int) ($layout['cards_per_column'] ?? 0));

        $columns = $requestedCols > 0 ? $requestedCols : max(0, $maxCols);
        $rows = $requestedRows > 0 ? $requestedRows : max(0, $maxRows);

        if (empty($errors) && $columns > $maxCols) {
            $labelUnit = $isDoubleSided ? 'pasangan kartu per baris' : 'kartu per baris';
            $errors[] = 'Jumlah ' . $labelUnit . ' (' . $columns . ') melebihi kapasitas lebar kertas (maksimal ' . $maxCols . ').';
        }

        if (empty($errors) && $rows > $maxRows) {
            $labelRow = $isDoubleSided ? 'baris pasangan per halaman' : 'baris kartu per halaman';
            $errors[] = 'Jumlah ' . $labelRow . ' (' . $rows . ') melebihi kapasitas tinggi kertas (maksimal ' . $maxRows . ').';
        }

        $fitsOnPage = empty($errors) && $columns >= 1 && $rows >= 1;

        if (! $fitsOnPage && $throwOnError) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $cardsPerPage = $fitsOnPage ? ($columns * $rows) : 0;
        $totalSidesPerPage = $isDoubleSided ? ($cardsPerPage * 2) : $cardsPerPage;
        $usedWidth = $fitsOnPage ? round(($columns * $unitWidth) + (max(0, $columns - 1) * $gapX), 3) : 0.0;
        $usedHeight = $fitsOnPage ? round(($rows * $unitHeight) + (max(0, $rows - 1) * $gapY), 3) : 0.0;
        $estimatedPages = ($cardsPerPage > 0 && $totalStudents > 0)
            ? (int) ceil($totalStudents / $cardsPerPage)
            : 0;

        return [
            'fits_on_page' => $fitsOnPage,
            'errors' => $errors,
            'card_side_mode' => $isDoubleSided ? 'double_sided' : 'single',
            'is_double_sided' => $isDoubleSided,
            'paper_size' => $paperSize,
            'orientation' => $orientation,
            'page_width_mm' => $paperWidth,
            'page_height_mm' => $paperHeight,
            'paper_width_mm' => $paperWidth,
            'paper_height_mm' => $paperHeight,
            'printable_width_mm' => $printableWidth,
            'printable_height_mm' => $printableHeight,
            'margin_top_mm' => $marginTop,
            'margin_right_mm' => $marginRight,
            'margin_bottom_mm' => $marginBottom,
            'margin_left_mm' => $marginLeft,
            'gap_x_mm' => $gapX,
            'gap_y_mm' => $gapY,
            'card_width_mm' => $cardWidth,
            'card_height_mm' => $cardHeight,
            'unit_width_mm' => $unitWidth,
            'unit_height_mm' => $unitHeight,
            'columns' => $columns,
            'rows' => $rows,
            'max_columns' => max(0, $maxCols),
            'max_rows' => max(0, $maxRows),
            'cards_per_page' => $cardsPerPage,
            'total_sides_per_page' => $totalSidesPerPage,
            'used_width_mm' => $usedWidth,
            'used_height_mm' => $usedHeight,
            'total_students' => $totalStudents,
            'total_pages' => $estimatedPages,
            'estimated_pages' => $estimatedPages,
        ];
    }

    private function resolvePaperDimensions(string $paperSize, array $layout): array
    {
        return match ($paperSize) {
            'F4' => [215.0, 330.0],
            'Letter' => [215.9, 279.4],
            'Custom' => [
                max(50.0, (float) ($layout['custom_paper_width_mm'] ?? 210.0)),
                max(50.0, (float) ($layout['custom_paper_height_mm'] ?? 297.0)),
            ],
            default => [210.0, 297.0],
        };
    }
}