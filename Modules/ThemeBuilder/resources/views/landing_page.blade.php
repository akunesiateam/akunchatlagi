<!DOCTYPE html>
<html lang="en">
    @php
    // Batch load all theme settings to avoid multiple database queries
    $themeSettings = get_batch_settings([
       
        'theme.customCss',
        'theme.custom_js_header',
    ]);

   
    $customCss = $themeSettings['theme.customCss'];
    $headerJs = $themeSettings['theme.custom_js_header'];
@endphp


<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <title>{{ $theme->name ?? 'Dragify' }}</title>
    @livewireStyles
</head>

<body>
    <div id="page-display"></div>

    <script>
        // Decode JSON strings if stored as JSON in DB
        const payloadHtml = <?= $theme->theme_html ? $theme->theme_html : '""'; ?>;
        const payloadCss = <?= $theme->theme_css ? $theme->theme_css : '""'; ?>;

        if (payloadHtml || payloadCss) {
            // Add CSS
            const style = document.createElement("style");
            style.textContent = payloadCss;
            document.head.appendChild(style);

            // Add HTML
            const container = document.getElementById("page-display");
            container.innerHTML = payloadHtml;
        } else {
            document.getElementById("page-display").innerHTML =
                "<h2>No saved page found.</h2>";
        }
    </script>
    @if (!empty($customCss))
    <style>
        {!! $customCss !!}
    </style>
@endif
@if (!empty($headerJs))
    {!! $headerJs !!}
@endif
    @livewireScripts
</body>

</html>