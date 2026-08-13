<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lumina Academy')</title>
    
    <!-- Gunakan Tailwind CSS via CDN / Vite -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">

    <div class="flex min-h-screen">
        <!-- Sidebar Lumina Academy -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between">
            <div>
                <!-- Logo & Brand -->
                <div class="p-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-200">
                        L
                    </div>
                    <div>
                        <h1 class="font-bold text-slate-900 leading-tight">Lumina</h1>
                        <p class="text-xs text-slate-500">Academy</p>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="px-4 space-y-1">
                    <a href="/mahasiswa/dashboard" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl {{ request()->is('mahasiswa/dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-grid-2"></i> Dashboard
                    </a>
                    <a href="/mahasiswa/course" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl {{ request()->is('mahasiswa/course*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-book-open"></i> Courses
                    </a>
                    <a href="/mahasiswa/assignment" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl {{ request()->is('mahasiswa/assignment*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-list-check"></i> Assignments
                    </a>
                    <a href="/mahasiswa/discussion" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl {{ request()->is('mahasiswa/discussion*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-comments"></i> Discussion
                    </a>
                    <a href="/mahasiswa/profile" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl {{ request()->is('mahasiswa/profile*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                </nav>
            </div>

            <!-- Bottom Menu -->
            <div class="p-4 border-t border-slate-100 space-y-1">
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 rounded-xl hover:bg-slate-50">
                    <i class="fa-solid fa-sparkles text-amber-500"></i> Lumina AI Help
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 rounded-xl hover:bg-slate-50">
                    <i class="fa-solid fa-gear"></i> Settings
                </a>
                <a href="/logout" class="flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 rounded-xl hover:bg-rose-50">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Header Bar -->
            <header class="h-20 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Selamat Datang, Mahasiswa</h2>
                    <p class="text-xs text-slate-500">Siap untuk melanjutkan pembelajaran hari ini?</p>
                </div>

                <!-- Stats Badges & Profile Dropdown -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-700">
                        <i class="fa-solid fa-bolt text-amber-500"></i>
                        <span>XP: 1,250</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-700">
                        <i class="fa-solid fa-fire text-orange-500"></i>
                        <span>5 Day Streak</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-700">
                        <i class="fa-solid fa-award text-indigo-500"></i>
                        <span>12 Badges</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-100 border border-indigo-200 flex items-center justify-center font-bold text-indigo-600 ml-2">
                        M
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-8 flex-1">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="p-6 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
                © 2026 Lumina Academy. Empowering University Students.
            </footer>
        </div>
    </div>

</body>
</html>