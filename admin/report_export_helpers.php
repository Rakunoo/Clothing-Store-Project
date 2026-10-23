<?php
// File purpose: Renders the admin sales report as a PDF document or an Excel workbook.
// These helpers only build file contents; the export endpoint handles access and headers.

/**
 * Small dependency-free PDF writer tailored to the sales report layout.
 * All vertical coordinates are measured from the top of an A4 page.
 */
final class SalesReportPdf
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN = 48.0;
    private const CONTENT_WIDTH = 499.28;
    private const CONTENT_LIMIT = 776.0;

    private const INK = [0.169, 0.169, 0.157];
    private const MUTED = [0.463, 0.447, 0.408];
    private const RULE = [0.898, 0.871, 0.824];
    private const ACCENT = [0.710, 0.396, 0.114];
    private const PANEL = [0.980, 0.969, 0.949];
    private const PANEL_LINE = [0.937, 0.902, 0.847];
    private const ZEBRA = [0.988, 0.984, 0.976];
    private const HEADER_FILL = [0.965, 0.945, 0.914];
    private const TOTAL_FILL = [0.957, 0.933, 0.898];

    private const HELVETICA_WIDTHS = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667,
        "'" => 191, '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333,
        '.' => 278, '/' => 278, '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556,
        '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556, ':' => 278, ';' => 278,
        '<' => 584, '=' => 584, '>' => 584, '?' => 556, '@' => 1015, 'A' => 667, 'B' => 667,
        'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
        'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667,
        'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944,
        'X' => 667, 'Y' => 667, 'Z' => 611, '[' => 278, '\\' => 278, ']' => 278, '^' => 469,
        '_' => 556, '`' => 333, 'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556,
        'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222,
        'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333, 's' => 500,
        't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 500,
        '{' => 334, '|' => 260, '}' => 334, '~' => 584,
    ];

    private array $pages = [];
    private string $buffer = '';
    private bool $started = false;
    private float $y = 0.0;

    public function __construct(
        private string $title,
        private string $brand,
        private string $generatedLabel
    ) {
    }

    /** Start a page and paint the report masthead. */
    public function addPage(): void
    {
        if ($this->started) {
            $this->pages[] = $this->buffer;
        }
        $this->buffer = '';
        $this->started = true;

        $isFirstPage = count($this->pages) === 0;
        $this->fillRect(0, 0, self::PAGE_WIDTH, 4, self::ACCENT);

        if ($isFirstPage) {
            $this->text(self::MARGIN, 58, 15, true, self::INK, strtoupper($this->brand));
            $this->text(self::MARGIN, 75, 7.5, true, self::MUTED, 'ADMIN REPORTING');
            $this->textRight(self::PAGE_WIDTH - self::MARGIN, 58, 8.5, false, self::MUTED, 'Generated ' . $this->generatedLabel);
            $this->textRight(self::PAGE_WIDTH - self::MARGIN, 74, 8.5, false, self::MUTED, 'Confidential - internal use only');
            $this->line(self::MARGIN, 94, self::PAGE_WIDTH - self::MARGIN, 94, 1, self::RULE);
            $this->text(self::MARGIN, 134, 27, true, self::INK, $this->title);
            $this->text(self::MARGIN, 156, 9.5, false, self::MUTED, 'Order status breakdown and top-selling products');
            $this->line(self::MARGIN, 172, self::PAGE_WIDTH - self::MARGIN, 172, 2, self::INK);
            $this->y = 196;
        } else {
            $this->text(self::MARGIN, 56, 12, true, self::INK, strtoupper($this->brand));
            $this->textRight(self::PAGE_WIDTH - self::MARGIN, 56, 8.5, false, self::MUTED, $this->title . ' (continued)');
            $this->line(self::MARGIN, 72, self::PAGE_WIDTH - self::MARGIN, 72, 1, self::RULE);
            $this->y = 98;
        }
    }

    /** Keep a block of the given height on the current page, or move it to a new one. */
    public function ensureSpace(float $height): void
    {
        if (!$this->started) {
            $this->addPage();
            return;
        }
        if ($this->y + $height > self::CONTENT_LIMIT) {
            $this->addPage();
        }
    }

    /** Four-up metric cards, one entry per report total. */
    public function summaryCards(array $cards): void
    {
        $count = count($cards);
        if ($count === 0) {
            return;
        }

        $gap = 10.0;
        $cardWidth = (self::CONTENT_WIDTH - ($gap * ($count - 1))) / $count;
        $cardHeight = 62.0;
        $this->ensureSpace($cardHeight + 18);

        $x = self::MARGIN;
        foreach ($cards as $card) {
            $top = $this->y;
            $this->fillRect($x, $top, $cardWidth, $cardHeight, self::PANEL);
            $this->line($x, $top, $x + $cardWidth, $top, 0.6, self::PANEL_LINE);
            $this->line($x, $top + $cardHeight, $x + $cardWidth, $top + $cardHeight, 0.6, self::PANEL_LINE);
            $this->line($x, $top, $x, $top + $cardHeight, 0.6, self::PANEL_LINE);
            $this->line($x + $cardWidth, $top, $x + $cardWidth, $top + $cardHeight, 0.6, self::PANEL_LINE);
            $this->fillRect($x, $top, 2.5, $cardHeight, self::ACCENT);
            $this->text($x + 12, $top + 20, 7.2, true, self::MUTED, self::fitText(strtoupper($card['label']), 7.2, true, $cardWidth - 24));
            $this->text($x + 12, $top + 45, 12.5, true, self::INK, self::fitText((string)$card['value'], 12.5, true, $cardWidth - 22));
            $x += $cardWidth + $gap;
        }

        $this->y += $cardHeight + 24;
    }

    /** Small explanatory line beneath a block. */
    public function note(string $text): void
    {
        $this->ensureSpace(20);
        $this->text(self::MARGIN, $this->y + 4, 8.2, false, self::MUTED, $text);
        $this->y += 18;
    }

    /** Section heading with a thin rule and optional right-aligned context. */
    public function sectionTitle(string $title, string $context = ''): void
    {
        $this->ensureSpace(44);
        $this->text(self::MARGIN, $this->y + 14, 14, true, self::INK, $title);
        if ($context !== '') {
            $this->textRight(self::PAGE_WIDTH - self::MARGIN, $this->y + 14, 8.5, false, self::MUTED, $context);
        }
        $this->line(self::MARGIN, $this->y + 24, self::PAGE_WIDTH - self::MARGIN, $this->y + 24, 0.8, self::RULE);
        $this->y += 34;
    }

    /**
     * Draw a table. Columns need label, width, and align; rows are plain value arrays.
     * A total row is rendered with emphasis beneath the body rows.
     */
    public function table(array $columns, array $rows, ?array $totalRow = null): void
    {
        $rowHeight = 24.0;
        $this->ensureSpace($rowHeight * 2);
        $this->drawHeaderRow($columns, $rowHeight);

        $zebra = false;
        foreach ($rows as $row) {
            if ($this->y + $rowHeight > self::CONTENT_LIMIT) {
                $this->addPage();
                $this->drawHeaderRow($columns, $rowHeight);
            }
            $this->drawRow($columns, $row, $rowHeight, $zebra, false);
            $zebra = !$zebra;
        }

        if ($totalRow !== null) {
            if ($this->y + $rowHeight > self::CONTENT_LIMIT) {
                $this->addPage();
                $this->drawHeaderRow($columns, $rowHeight);
            }
            $this->drawRow($columns, $totalRow, $rowHeight, false, true);
        }

        $this->y += 14;
    }

    /** Panel used when a report section has no data. */
    public function emptyState(string $text): void
    {
        $this->ensureSpace(52);
        $this->fillRect(self::MARGIN, $this->y, self::CONTENT_WIDTH, 40, self::PANEL);
        $this->text(self::MARGIN + 12, $this->y + 25, 9.5, false, self::MUTED, $text);
        $this->y += 52;
    }

    /** Serialize every buffered page into one PDF document. */
    public function output(): string
    {
        if (!$this->started) {
            $this->addPage();
        }
        $this->pages[] = $this->buffer;
        $this->buffer = '';

        $pageCount = count($this->pages);
        foreach ($this->pages as $index => $content) {
            $saved = $this->buffer;
            $this->buffer = '';
            $this->line(self::MARGIN, 796, self::PAGE_WIDTH - self::MARGIN, 796, 0.7, self::RULE);
            $this->text(self::MARGIN, 812, 7.8, false, self::MUTED, strtoupper($this->brand) . ' - ' . $this->title);
            $this->textRight(self::PAGE_WIDTH - self::MARGIN, 812, 7.8, false, self::MUTED, 'Page ' . ($index + 1) . ' of ' . $pageCount);
            $footer = $this->buffer;
            $this->buffer = $saved;
            $this->pages[$index] = $content . $footer;
        }

        $maxObjectId = 5 + ($pageCount * 2);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($index = 0; $index < $pageCount; $index++) {
            $kids[] = (6 + ($index * 2)) . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Title (' . self::escape(self::encode($this->brand . ' ' . $this->title)) . ') /Author (Clothify) /Creator (Clothify Admin Reports) /Producer (Clothify) /CreationDate (D:' . gmdate('YmdHis') . 'Z) >>';

        for ($index = 0; $index < $pageCount; $index++) {
            $pageId = 6 + ($index * 2);
            $contentId = $pageId + 1;
            $stream = $this->pages[$index];
            if (!str_ends_with($stream, "\n")) {
                $stream .= "\n";
            }
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        for ($objectId = 1; $objectId <= $maxObjectId; $objectId++) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId . " 0 obj\n" . $objects[$objectId] . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n0000000000 65535 f \n";
        for ($objectId = 1; $objectId <= $maxObjectId; $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }
        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function drawHeaderRow(array $columns, float $rowHeight): void
    {
        $top = $this->y;
        $this->fillRect(self::MARGIN, $top, self::CONTENT_WIDTH, $rowHeight, self::HEADER_FILL);

        $x = self::MARGIN;
        foreach ($columns as $column) {
            $isRight = ($column['align'] ?? 'left') === 'right';
            $textX = $isRight ? $x + $column['width'] - 10 : $x + 10;
            $this->text($textX, $top + 16, 7.5, true, self::MUTED, strtoupper($column['label']), $isRight ? 'right' : 'left');
            $x += $column['width'];
        }

        $this->line(self::MARGIN, $top + $rowHeight, self::MARGIN + self::CONTENT_WIDTH, $top + $rowHeight, 0.8, self::RULE);
        $this->y += $rowHeight;
    }

    private function drawRow(array $columns, array $row, float $rowHeight, bool $zebra, bool $isTotal): void
    {
        $top = $this->y;
        if ($isTotal) {
            $this->fillRect(self::MARGIN, $top, self::CONTENT_WIDTH, $rowHeight, self::TOTAL_FILL);
            $this->line(self::MARGIN, $top, self::MARGIN + self::CONTENT_WIDTH, $top, 1, self::INK);
        } elseif ($zebra) {
            $this->fillRect(self::MARGIN, $top, self::CONTENT_WIDTH, $rowHeight, self::ZEBRA);
        }

        $x = self::MARGIN;
        foreach (array_values($row) as $index => $value) {
            if (!isset($columns[$index])) {
                break;
            }
            $column = $columns[$index];
            $isRight = ($column['align'] ?? 'left') === 'right';
            $textX = $isRight ? $x + $column['width'] - 10 : $x + 10;
            $display = self::fitText((string)$value, 9.2, $isTotal, $column['width'] - 20);
            $this->text($textX, $top + 16, 9.2, $isTotal, self::INK, $display, $isRight ? 'right' : 'left');
            $x += $column['width'];
        }

        $this->line(self::MARGIN, $top + $rowHeight, self::MARGIN + self::CONTENT_WIDTH, $top + $rowHeight, 0.5, self::RULE);
        $this->y += $rowHeight;
    }

    private function fillRect(float $x, float $top, float $width, float $height, array $color): void
    {
        $y = self::PAGE_HEIGHT - $top - $height;
        $this->buffer .= sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $width,
            $height
        );
    }

    private function line(float $x1, float $top1, float $x2, float $top2, float $width, array $color): void
    {
        $this->buffer .= sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $color[0],
            $color[1],
            $color[2],
            $width,
            $x1,
            self::PAGE_HEIGHT - $top1,
            $x2,
            self::PAGE_HEIGHT - $top2
        );
    }

    private function text(float $x, float $top, float $size, bool $bold, array $color, string $value, string $align = 'left'): void
    {
        $encoded = self::encode($value);
        if ($encoded === '') {
            return;
        }

        if ($align === 'right') {
            $x -= self::textWidth($encoded, $size, $bold);
        } elseif ($align === 'center') {
            $x -= self::textWidth($encoded, $size, $bold) / 2;
        }

        $this->buffer .= sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $bold ? 'F2' : 'F1',
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            self::PAGE_HEIGHT - $top,
            $encoded
        );
    }

    private function textRight(float $rightX, float $top, float $size, bool $bold, array $color, string $value): void
    {
        $this->text($rightX, $top, $size, $bold, $color, $value, 'right');
    }

    /** Convert UTF-8 copy into the single-byte encoding used by the core PDF fonts. */
    private static function encode(string $value): string
    {
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? '';
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
        }

        return self::escape($converted);
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private static function textWidth(string $value, float $size, bool $bold): float
    {
        $width = 0;
        $length = strlen($value);
        for ($index = 0; $index < $length; $index++) {
            $width += self::HELVETICA_WIDTHS[$value[$index]] ?? 556;
        }

        return ($width / 1000) * $size * ($bold ? 1.02 : 1.0);
    }

    private static function fitText(string $value, float $size, bool $bold, float $maxWidth): string
    {
        if (self::textWidth($value, $size, $bold) <= $maxWidth) {
            return $value;
        }

        $trimmed = $value;
        while ($trimmed !== '' && self::textWidth($trimmed . '...', $size, $bold) > $maxWidth) {
            $trimmed = mb_substr($trimmed, 0, -1, 'UTF-8');
        }

        return rtrim($trimmed) . '...';
    }
}

/** Build the complete PDF report for the supplied report data. */
function buildSalesReportPdf(array $summary, array $statusCounts, array $topProducts, string $generatedLabel): string
{
    $netOrders = (int)($summary['order_count'] ?? 0);
    $netRevenue = (float)($summary['total_revenue'] ?? 0);
    $totalOrders = 0;
    $cancelledOrders = 0;

    foreach ($statusCounts as $row) {
        $count = (int)($row['count'] ?? 0);
        $totalOrders += $count;
        if (strcasecmp((string)($row['status'] ?? ''), 'Cancelled') === 0) {
            $cancelledOrders += $count;
        }
    }

    $averageOrderValue = $netOrders > 0 ? $netRevenue / $netOrders : 0.0;

    $pdf = new SalesReportPdf('Sales Report', 'Clothify', $generatedLabel);
    $pdf->addPage();
    $pdf->summaryCards([
        ['label' => 'Net Orders', 'value' => number_format($netOrders)],
        ['label' => 'Net Revenue (PHP)', 'value' => number_format($netRevenue, 2)],
        ['label' => 'Cancelled Orders', 'value' => number_format($cancelledOrders)],
        ['label' => 'Avg. Order Value (PHP)', 'value' => number_format($averageOrderValue, 2)],
    ]);
    $pdf->note('Revenue, order counts, and averages exclude cancelled orders.');

    $pdf->sectionTitle('Orders by Status', number_format($totalOrders) . ' order' . ($totalOrders === 1 ? '' : 's') . ' recorded');
    if (count($statusCounts) === 0) {
        $pdf->emptyState('No orders have been recorded yet.');
    } else {
        $rows = [];
        foreach ($statusCounts as $row) {
            $rows[] = [ucfirst(strtolower((string)$row['status'])), number_format((int)$row['count'])];
        }
        $pdf->table(
            [
                ['label' => 'Status', 'width' => 399.28, 'align' => 'left'],
                ['label' => 'Orders', 'width' => 100.0, 'align' => 'right'],
            ],
            $rows,
            ['Total', number_format($totalOrders)]
        );
    }

    $pdf->sectionTitle('Top Selling Products', 'Ranked by units sold');
    if (count($topProducts) === 0) {
        $pdf->emptyState('No product sales are available yet.');
    } else {
        $rows = [];
        $unitsSold = 0;
        $productRevenue = 0.0;
        foreach ($topProducts as $product) {
            $units = (int)$product['total_sold'];
            $revenue = (float)$product['total_revenue'];
            $unitsSold += $units;
            $productRevenue += $revenue;
            $rows[] = [
                (string)$product['name'],
                number_format($units),
                'PHP ' . number_format($revenue, 2),
            ];
        }
        $pdf->table(
            [
                ['label' => 'Product', 'width' => 319.28, 'align' => 'left'],
                ['label' => 'Units Sold', 'width' => 80.0, 'align' => 'right'],
                ['label' => 'Revenue', 'width' => 100.0, 'align' => 'right'],
            ],
            $rows,
            ['Total', number_format($unitsSold), 'PHP ' . number_format($productRevenue, 2)]
        );
    }

    return $pdf->output();
}

/** Build the complete Excel workbook (.xlsx) for the supplied report data. */
function buildSalesReportXlsx(array $summary, array $statusCounts, array $topProducts, string $generatedLabel, DateTimeImmutable $generatedAt): string
{
    $netOrders = (int)($summary['order_count'] ?? 0);
    $netRevenue = (float)($summary['total_revenue'] ?? 0);
    $totalOrders = 0;
    $cancelledOrders = 0;

    foreach ($statusCounts as $row) {
        $count = (int)($row['count'] ?? 0);
        $totalOrders += $count;
        if (strcasecmp((string)($row['status'] ?? ''), 'Cancelled') === 0) {
            $cancelledOrders += $count;
        }
    }
    $averageOrderValue = $netOrders > 0 ? $netRevenue / $netOrders : 0.0;

    $rowsXml = '';
    $merges = [];
    $rowNumber = 0;

    $escape = static function ($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };

    $textCell = static function (int $row, string $column, string $value, int $style) use ($escape): string {
        return '<c r="' . $column . $row . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $escape($value) . '</t></is></c>';
    };

    $numberCell = static function (int $row, string $column, $value, int $style): string {
        return '<c r="' . $column . $row . '" s="' . $style . '"><v>' . $value . '</v></c>';
    };

    $addRow = static function (array $cells, ?float $height = null) use (&$rowsXml, &$rowNumber): void {
        $rowNumber++;
        $attributes = ' r="' . $rowNumber . '"';
        if ($height !== null) {
            $attributes .= ' ht="' . number_format($height, 2, '.', '') . '" customHeight="1"';
        }
        $rowsXml .= '<row' . $attributes . '>' . implode('', $cells) . '</row>';
    };

    $addMergedRow = static function (string $value, int $style, float $height) use (&$rowNumber, $addRow, $textCell, &$merges): void {
        $row = $rowNumber + 1;
        $addRow([
            $textCell($row, 'A', $value, $style),
            $textCell($row, 'B', '', $style),
            $textCell($row, 'C', '', $style),
        ], $height);
        $merges[] = 'A' . $row . ':C' . $row;
    };

    $addSection = static function (string $title) use ($addMergedRow): void {
        $addMergedRow($title, 3, 24);
    };

    $addMergedRow('CLOTHIFY', 12, 24);
    $addMergedRow('Sales Report', 11, 24);
    $addMergedRow('Generated ' . $generatedLabel . '   |   Confidential - internal use only', 2, 20);
    $addRow([], 10);

    $addSection('SALES SUMMARY');
    $summaryRows = [
        ['Net Orders', (string)$netOrders, 10],
        ['Net Revenue (PHP)', number_format($netRevenue, 2, '.', ''), 9],
        ['Cancelled Orders', (string)$cancelledOrders, 10],
        ['Avg. Order Value (PHP)', number_format($averageOrderValue, 2, '.', ''), 9],
    ];
    foreach ($summaryRows as $summaryRow) {
        $row = $rowNumber + 1;
        $addRow([
            $textCell($row, 'A', $summaryRow[0], 8),
            $numberCell($row, 'B', $summaryRow[1], $summaryRow[2]),
        ], 20);
    }
    $addRow([], 10);

    $addSection('ORDERS BY STATUS');
    $row = $rowNumber + 1;
    $addRow([
        $textCell($row, 'A', 'Status', 4),
        $textCell($row, 'B', 'Orders', 13),
    ], 20);

    if (count($statusCounts) === 0) {
        $addMergedRow('No orders have been recorded yet.', 5, 20);
    } else {
        foreach ($statusCounts as $statusRow) {
            $row = $rowNumber + 1;
            $addRow([
                $textCell($row, 'A', ucfirst(strtolower((string)$statusRow['status'])), 5),
                $numberCell($row, 'B', (int)$statusRow['count'], 7),
            ], 19);
        }
        $row = $rowNumber + 1;
        $addRow([
            $textCell($row, 'A', 'Total', 8),
            $numberCell($row, 'B', $totalOrders, 10),
        ], 20);
    }
    $addRow([], 10);

    $addSection('TOP SELLING PRODUCTS');
    $row = $rowNumber + 1;
    $addRow([
        $textCell($row, 'A', 'Product', 4),
        $textCell($row, 'B', 'Units Sold', 13),
        $textCell($row, 'C', 'Revenue (PHP)', 14),
    ], 20);

    if (count($topProducts) === 0) {
        $addMergedRow('No product sales are available yet.', 5, 20);
    } else {
        $unitsSold = 0;
        $productRevenue = 0.0;
        foreach ($topProducts as $product) {
            $units = (int)$product['total_sold'];
            $revenue = (float)$product['total_revenue'];
            $unitsSold += $units;
            $productRevenue += $revenue;
            $row = $rowNumber + 1;
            $addRow([
                $textCell($row, 'A', (string)$product['name'], 5),
                $numberCell($row, 'B', $units, 7),
                $numberCell($row, 'C', number_format($revenue, 2, '.', ''), 6),
            ], 19);
        }
        $row = $rowNumber + 1;
        $addRow([
            $textCell($row, 'A', 'Total', 8),
            $numberCell($row, 'B', $unitsSold, 10),
            $numberCell($row, 'C', number_format($productRevenue, 2, '.', ''), 9),
        ], 20);
    }

    $addRow([], 10);
    $addMergedRow('Revenue and order averages exclude cancelled orders. Generated from live Clothify order data.', 2, 18);

    $mergeXml = '';
    if (count($merges) > 0) {
        $mergeXml = '<mergeCells count="' . count($merges) . '">';
        foreach ($merges as $merge) {
            $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
        }
        $mergeXml .= '</mergeCells>';
    }

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="A1:C' . $rowNumber . '"/>'
        . '<sheetViews><sheetView showGridLines="0" workbookViewId="0"><selection activeCell="A1" sqref="A1"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="2" width="16" customWidth="1"/><col min="3" max="3" width="22" customWidth="1"/></cols>'
        . '<sheetData>' . $rowsXml . '</sheetData>'
        . $mergeXml
        . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
        . '<pageSetup orientation="portrait" paperSize="9"/>'
        . '</worksheet>';

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;PHP &quot;#,##0.00"/></numFmts>'
        . '<fonts count="8">'
        . '<font><sz val="11"/><color rgb="FF2B2B28"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="18"/><color rgb="FF2B2B28"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><sz val="10"/><color rgb="FF767268"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FF2B2B28"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="12"/><color rgb="FF8F4F16"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="14"/><color rgb="FF2B2B28"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FF8F4F16"/><name val="Calibri"/><family val="2"/></font>'
        . '</fonts>'
        . '<fills count="5">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF2B2B28"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFAF7F2"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF5EFE6"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left/><right/><top/><bottom style="thin"><color rgb="FFE5DED2"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="15">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>';

    $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Sales Report" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $timestamp = $generatedAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    $coreProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>Clothify Sales Report</dc:title>'
        . '<dc:creator>Clothify Admin</dc:creator>'
        . '<cp:lastModifiedBy>Clothify Admin</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
        . '</cp:coreProperties>';

    $appProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>Clothify Admin</Application>'
        . '</Properties>';

    $files = [
        '[Content_Types].xml' => $contentTypes,
        '_rels/.rels' => $rootRels,
        'docProps/core.xml' => $coreProps,
        'docProps/app.xml' => $appProps,
        'xl/workbook.xml' => $workbookXml,
        'xl/_rels/workbook.xml.rels' => $workbookRels,
        'xl/styles.xml' => $stylesXml,
        'xl/worksheets/sheet1.xml' => $sheetXml,
    ];

    $tempPath = tempnam(sys_get_temp_dir(), 'clothify-xlsx-');
    if ($tempPath === false) {
        throw new RuntimeException('A temporary file could not be created for the workbook.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($tempPath);
        throw new RuntimeException('The Excel workbook could not be created.');
    }

    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    if (!$zip->close()) {
        @unlink($tempPath);
        throw new RuntimeException('The Excel workbook could not be finalized.');
    }

    $binary = file_get_contents($tempPath);
    @unlink($tempPath);
    if ($binary === false) {
        throw new RuntimeException('The Excel workbook could not be read.');
    }

    return $binary;
}
