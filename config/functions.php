<?php
/**
 * POS Cafe Helper Functions
 */

// Generate random token
function gen_token($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

// Format money
function fmt_money($amount) {
    return '₹' . number_format($amount, 2);
}

// Per page selector HTML
function per_page_selector($current) {
    $options = [10, 25, 50, 100];
    $html = '<select class="form-select form-select-sm" onchange="this.form.submit()" style="width:80px;">';
    foreach ($options as $opt) {
        $sel = $opt == $current ? 'selected' : '';
        $html .= "<option value='$opt' $sel>$opt</option>";
    }
    $html .= '</select>';
    return $html;
}
?>

