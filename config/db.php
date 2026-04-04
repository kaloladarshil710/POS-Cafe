<?php
// ============================================================
// POS Cafe — Secure Database Configuration
// ============================================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pos_cafe";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// ── Security helpers ──────────────────────────────────────

/** Return a safe integer from any input */
function safe_int($val, int $default = 0): int {
    return filter_var($val, FILTER_VALIDATE_INT) !== false ? intval($val) : $default;
}

/** Strip tags and escape for HTML output */
function h($val): string {
    return htmlspecialchars(strip_tags((string)$val), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Validate sort column against a whitelist — prevents ORDER BY injection */
function safe_sort_col(string $col, array $allowed, string $default): string {
    return in_array($col, $allowed, true) ? $col : $default;
}

/** Validate direction */
function safe_dir(string $dir): string {
    return strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
}

/**
 * Build a pagination URL keeping all current GET params intact,
 * overriding only 'page' (and optionally sort/dir).
 */
function page_url(int $page, array $extra = []): string {
    $params = array_merge($_GET, ['page' => $page], $extra);
    return '?' . http_build_query($params);
}

/**
 * Render pagination controls.
 * Returns HTML string.
 */
function pagination_html(int $total, int $per_page, int $current_page): string {
    if ($total <= $per_page) return '';
    $total_pages = (int)ceil($total / $per_page);
    if ($total_pages <= 1) return '';

    $html  = '<div class="pagination">';
    $html .= '<span class="pg-info">Showing ' . (($current_page - 1) * $per_page + 1) . '–' . min($current_page * $per_page, $total) . ' of ' . $total . '</span>';
    $html .= '<div class="pg-btns">';

    // Prev
    if ($current_page > 1) {
        $html .= '<a class="pg-btn" href="' . page_url($current_page - 1) . '">‹ Prev</a>';
    }

    // Page numbers (show window of 5)
    $start = max(1, $current_page - 2);
    $end   = min($total_pages, $current_page + 2);
    if ($start > 1)            $html .= '<a class="pg-btn" href="' . page_url(1) . '">1</a>' . ($start > 2 ? '<span class="pg-ellipsis">…</span>' : '');
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current_page ? ' pg-active' : '';
        $html  .= '<a class="pg-btn' . $active . '" href="' . page_url($i) . '">' . $i . '</a>';
    }
    if ($end < $total_pages)   $html .= ($end < $total_pages - 1 ? '<span class="pg-ellipsis">…</span>' : '') . '<a class="pg-btn" href="' . page_url($total_pages) . '">' . $total_pages . '</a>';

    // Next
    if ($current_page < $total_pages) {
        $html .= '<a class="pg-btn" href="' . page_url($current_page + 1) . '">Next ›</a>';
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Render a sortable <th> link.
 * $col = DB column name, $label = display text, $current_col, $current_dir
 */
function sort_th(string $col, string $label, string $current_col, string $current_dir): string {
    $is_active = $col === $current_col;
    $next_dir  = ($is_active && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    $arrow     = '';
    if ($is_active) $arrow = $current_dir === 'ASC' ? ' ↑' : ' ↓';
    $url = page_url(1, ['sort' => $col, 'dir' => $next_dir]);
    $cls = $is_active ? ' class="th-active"' : '';
    return '<th' . $cls . '><a class="sort-link" href="' . $url . '">' . h($label) . $arrow . '</a></th>';
}
