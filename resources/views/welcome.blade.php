@php
// Batch load site logo setting to avoid multiple database queries
$themeSettings = get_batch_settings([ 'theme.custom_js_footer','theme.custom_js_header']);

$footerJs = $themeSettings['theme.custom_js_footer'] ?? '';

$data = [
"theme" => $data,
"view" => "default_landing_page"
];
$render_landing_page = apply_filters('render_landing_page', $data);
echo view($render_landing_page['view'], ['theme' => $render_landing_page['theme']]);

@endphp
@if (!empty($footerJs))
{!! $footerJs !!}
@endif