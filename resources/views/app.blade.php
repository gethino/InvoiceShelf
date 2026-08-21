<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ Request::routeIs('customer.dashboard') ? get_page_title(!Request::header('company')) : ($tripoli_branding['meta_title'] ?? get_page_title(!Request::header('company'))) }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @if(!empty($tripoli_branding['meta_description']))
        <meta name="description" content="{{ $tripoli_branding['meta_description'] }}">
    @endif
    @if(!empty($tripoli_branding['favicon_url']))
        <link rel="icon" type="image/png" href="{{ $tripoli_branding['favicon_url'] }}">
    @else
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="shortcut icon" href="/favicon.ico">
    @endif
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="Tripoli Center">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="{{ $tripoli_branding['theme_color'] ?? '#ffffff' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Module Styles -->
    @foreach(\App\Services\Module\ModuleFacade::allStyles() as $name => $path)
        <link rel="stylesheet" href="/modules/styles/{{ $name }}">
    @endforeach

    @vite('resources/scripts/main.js')
</head>

<body
    class="h-full overflow-hidden bg-gray-100 font-base
    @if(isset($current_theme)) theme-{{ $current_theme }} @else theme-{{get_app_setting('admin_portal_theme') ?? 'invoiceshelf'}} @endif ">

    <!-- Module Scripts -->
    @foreach (\App\Services\Module\ModuleFacade::allScripts() as $name => $path)
        @if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']))
            <script type="module" src="{!! $path !!}"></script>
        @else
            <script type="module" src="/modules/scripts/{{ $name }}"></script>
        @endif
    @endforeach

    <script type="module">
        @if(isset($customer_logo))

        window.customer_logo = "/storage/{{$customer_logo}}"

        @endif
        @if(isset($login_page_logo))

        window.login_page_logo = "/storage/{{$login_page_logo}}"

        @endif
        @if(isset($login_page_heading))

        window.login_page_heading = "{{$login_page_heading}}"

        @endif
        @if(isset($login_page_description))

        window.login_page_description = "{{$login_page_description}}"

        @endif
        @if(isset($copyright_text))

        window.copyright_text = "{{$copyright_text}}"

        @endif

        @if(config('app.env') === 'demo')
            window.demo_mode = true
        @endif

        window.tripoli_branding = @json($tripoli_branding ?? null)

        window.InvoiceShelf.start()
    </script>
</body>

</html>
