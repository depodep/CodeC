<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIATRACK')</title>

    <!-- Anti-flicker Instant Layout State Check -->
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-[#fcfbfb] text-gray-800 antialiased min-h-screen flex" 
      x-data="{ sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true' }" 
      @sidebar-toggle.window="sidebarCollapsed = $event.detail">

    <!-- Fixed Permanent Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Dynamic Content with Dynamic Margin Transition -->
    <main id="app-main" 
          class="flex-1 flex flex-col min-w-0 pl-72 transition-all duration-300"
          :class="sidebarCollapsed ? 'pl-20' : 'pl-72'">
        @yield('content')
    </main>

    @stack('scripts')
<!-- UNIVERSAL NFC LISTENER -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    setInterval(() => {
        const inputs = document.querySelectorAll('input');
        let nfcInput = null;
        
        inputs.forEach(el => {
            if (el.placeholder && el.placeholder.toUpperCase().includes('ACR122U')) {
                nfcInput = el;
            }
        });
        
        if (nfcInput) {
            fetch('/api/nfc/latest-tap')
                .then(r => r.json())
                .then(data => {
                    if (data && data.uid && nfcInput.value !== data.uid) {
                        nfcInput.value = data.uid;
                        
                        const oldBg = nfcInput.style.backgroundColor;
                        nfcInput.style.backgroundColor = '#d1fae5';
                        nfcInput.style.borderColor = '#10b981';
                        nfcInput.style.color = '#047857';
                        nfcInput.style.fontWeight = '900';
                        
                        setTimeout(() => {
                            nfcInput.style.backgroundColor = oldBg;
                            nfcInput.style.borderColor = '';
                            nfcInput.style.color = '';
                        }, 1500);
                    }
                }).catch(e => {});
        }
    }, 1000);
});
</script>
</body>
</html>