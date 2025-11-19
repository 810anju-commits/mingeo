<?php
function formatDate(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d M Y', $timestamp) : $date;
}

function renderBars(array $dataset, array $colors): string
{
    if (empty($dataset)) {
        return '<p class="muted">No data available</p>';
    }
    $max = max(array_map(fn($row) => max($row), $dataset));
    if ($max <= 0) {
        $max = 1;
    }
    $html = '<div class="bars">';
    foreach ($dataset as $label => $values) {
        $html .= '<div class="bar-group">';
        $html .= '<div class="bar-label">' . htmlspecialchars($label) . '</div>';
        foreach ($values as $key => $value) {
            $height = ($value / $max) * 120;
            $color = $colors[$key] ?? '#0065a3';
            $html .= '<div class="bar" title="' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ': ' . (int) $value . '" style="height:' . $height . 'px;background:' . $color . '"><span>' . (int) $value . '</span></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function renderCard(string $label, int $value, string $colorClass, string $icon = ''): string
{
    $iconHtml = $icon ? '<span class="icon">' . $icon . '</span>' : '';
    return "<div class='card {$colorClass}'>{$iconHtml}<p class='label'>{$label}</p><p class='value'>{$value}</p></div>";
}
