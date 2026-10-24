<?php
// File purpose: Shared SVG bar chart used by the dashboard and the sales report.

if (!function_exists('salesChartMaxValue')) {
    /**
     * Choose a readable upper bound and grid step for the chart.
     * The returned value is always divided into four grid intervals.
     */
    function salesChartMaxValue(array $series) {
        $max = 0.0;
        foreach ($series as $point) {
            $max = max($max, (float)($point['revenue'] ?? 0));
        }
        if ($max <= 0) {
            return 100.0;
        }

        $roughStep = $max / 4;
        $magnitude = 10 ** floor(log10($roughStep));
        $residual = $roughStep / $magnitude;
        foreach ([1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $step) {
            if ($residual <= $step) {
                return $step * $magnitude * 4;
            }
        }

        return 10 * $magnitude * 4;
    }
}

if (!function_exists('renderSalesChart')) {
    /**
     * Render a responsive bar chart from a sales series.
     * Each series entry needs label, full_label, and revenue.
     */
    function renderSalesChart(array $series, string $title, string $description, string $idPrefix = 'sales-chart') {
        $series = array_values($series);
        $count = count($series);
        if ($count === 0) {
            echo '<p class="empty-state">No sales data is available for this period.</p>';
            return;
        }

        $chartMax = salesChartMaxValue($series);
        $plotLeft = 78.0;
        $plotRight = 940.0;
        $plotTop = 40.0;
        $plotBottom = 250.0;
        $plotWidth = $plotRight - $plotLeft;
        $plotHeight = $plotBottom - $plotTop;
        $slot = $plotWidth / $count;
        $barWidth = min(34.0, max(4.0, $slot * 0.58));
        $labelEvery = max(1, (int)ceil($count / 12));
        $yDecimals = $chartMax < 10 ? 2 : 0;
        $minWidth = max(680, $count * 20);
        $titleId = $idPrefix . '-title';
        $descId = $idPrefix . '-desc';
        ?>
        <svg class="sales-chart" style="min-width: <?php echo (int)$minWidth; ?>px" viewBox="0 0 960 330" role="img" aria-labelledby="<?php echo htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($descId, ENT_QUOTES, 'UTF-8'); ?>" preserveAspectRatio="xMidYMid meet">
            <title id="<?php echo htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
            <desc id="<?php echo htmlspecialchars($descId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></desc>
            <?php for ($grid = 0; $grid <= 4; $grid++):
                $gridY = $plotBottom - ($grid * ($plotHeight / 4));
                $gridValue = $chartMax * $grid / 4;
                ?>
                <line x1="<?php echo number_format($plotLeft, 1, '.', ''); ?>" y1="<?php echo number_format($gridY, 1, '.', ''); ?>" x2="<?php echo number_format($plotRight, 1, '.', ''); ?>" y2="<?php echo number_format($gridY, 1, '.', ''); ?>" class="sales-chart-gridline" />
                <text x="62" y="<?php echo number_format($gridY + 5, 1, '.', ''); ?>" text-anchor="end" class="sales-chart-y-label"><?php echo number_format($gridValue, $yDecimals); ?></text>
            <?php endfor; ?>
            <?php foreach ($series as $index => $point):
                $revenue = (float)($point['revenue'] ?? 0);
                $barHeight = $chartMax > 0 ? ($revenue / $chartMax) * $plotHeight : 0;
                $barX = $plotLeft + ($slot * $index) + (($slot - $barWidth) / 2);
                $barY = $plotBottom - $barHeight;
                $label = (string)($point['label'] ?? '');
                $fullLabel = (string)($point['full_label'] ?? $label);
                ?>
                <rect x="<?php echo number_format($barX, 2, '.', ''); ?>" y="<?php echo number_format($barY, 2, '.', ''); ?>" width="<?php echo number_format($barWidth, 2, '.', ''); ?>" height="<?php echo number_format($barHeight, 2, '.', ''); ?>" rx="4" class="sales-chart-bar">
                    <title><?php echo htmlspecialchars($fullLabel . ': PHP ' . number_format($revenue, 2), ENT_QUOTES, 'UTF-8'); ?></title>
                </rect>
                <?php if ($index % $labelEvery === 0 || $index === $count - 1): ?>
                    <text x="<?php echo number_format($barX + ($barWidth / 2), 2, '.', ''); ?>" y="280" text-anchor="middle" class="sales-chart-x-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></text>
                <?php endif; ?>
            <?php endforeach; ?>
        </svg>
        <?php
    }
}
