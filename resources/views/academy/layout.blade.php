<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pilot Academy')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#1463ff',
                        navy: '#0a2540',
                        ok: '#19a86b',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .prose-lesson h2 { font-size: 1.35rem; font-weight: 700; margin: 1.2rem 0 .5rem; color: #0a2540; }
        .prose-lesson h3 { font-size: 1.1rem; font-weight: 700; margin: 1rem 0 .4rem; color: #0a2540; }
        .prose-lesson p { margin: .6rem 0; line-height: 1.7; color: #334155; }
        .prose-lesson ul { list-style: disc; margin: .6rem 0 .6rem 1.4rem; color: #334155; line-height: 1.7; }
        .prose-lesson strong { color: #0a2540; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
            <a href="{{ route('academy.home') }}" class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand to-navy flex items-center justify-center text-white font-extrabold">P</span>
                <span class="font-extrabold text-navy text-lg">Pilot <span class="text-brand">Academy</span></span>
            </a>
            <div class="flex items-center gap-3">
                @php($name = session('student_name'))
                @if($name)
                    <span class="hidden sm:block text-sm text-slate-500">{{ $name }}</span>
                    <span class="w-9 h-9 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(mb_substr($name, 0, 1)) }}
                    </span>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-5 py-8">
        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-5 py-10 text-center text-sm text-slate-400">
        Pilot Academy · internal training
    </footer>
</body>
</html>
