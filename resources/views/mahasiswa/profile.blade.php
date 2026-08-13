@extends('layouts.mahasiswa')

@section('title', 'Profile & Settings - Lumina Academy')

@section('content')
<div class="space-y-6">

    <!-- Top Bar Header (Title & Search/Profile Header) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Profile & Settings</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your academic profile, security, and application preferences.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Search Bar -->
            <div class="relative w-64 md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" 
                       placeholder="Search courses, documents..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-100/80 border border-transparent rounded-full text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <!-- Header Action Icons -->
            <div class="flex items-center gap-2">
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-moon"></i>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-bell"></i>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-star"></i>
                </button>
                <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=100&auto=format&fit=crop" alt="User" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout (8 Cols Left, 4 Cols Right) -->
    <div class="grid grid-cols-12 gap-6 items-start pt-2">

        <!-- ================= LEFT COLUMN: PERSONAL INFO & SECURITY (8 Cols) ================= -->
        <div class="col-span-12 lg:col-span-8 space-y-6">

            <!-- Card 1: Personal Information -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                        <i class="fa-regular fa-user text-indigo-600"></i>
                        <h2>Personal Information</h2>
                    </div>
                    <button class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center gap-1">
                        <i class="fa-solid fa-pen text-[10px]"></i> Edit
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <!-- Avatar & Change Photo -->
                    <div class="flex flex-col items-center gap-2 shrink-0">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-slate-100 shadow-sm">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop" 
                                 alt="Profile Photo" 
                                 class="w-full h-full object-cover">
                        </div>
                        <button class="text-[10px] font-bold tracking-wider text-slate-500 hover:text-indigo-600 uppercase transition-colors mt-1">
                            CHANGE PHOTO
                        </button>
                    </div>

                    <!-- Read-only / Editable Form Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                        <!-- Full Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Full Name</label>
                            <input type="text" 
                                   value="Ahmad Mahasiswa" 
                                   readonly 
                                   class="w-full px-3.5 py-2.5 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none">
                        </div>

                        <!-- NIM (Student ID) -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">NIM (Student ID)</label>
                            <input type="text" 
                                   value="1234567890" 
                                   readonly 
                                   class="w-full px-3.5 py-2.5 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none">
                        </div>

                        <!-- Study Program -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Study Program (Prodi)</label>
                            <input type="text" 
                                   value="Teknik Informatika" 
                                   readonly 
                                   class="w-full px-3.5 py-2.5 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none">
                        </div>

                        <!-- Academic Email -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-600">Academic Email</label>
                            <input type="email" 
                                   value="ahmad.m@student.univ.edu" 
                                   readonly 
                                   class="w-full px-3.5 py-2.5 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Security & Password -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                        <i class="fa-solid fa-lock text-amber-500"></i>
                        <h2>Security & Password</h2>
                    </div>
                    <p class="text-xs text-slate-500">Ensure your account is using a long, random password to stay secure.</p>
                </div>

                <hr class="border-slate-100">

                <form action="#" method="POST" class="space-y-4 max-w-md">
                    @csrf
                    <!-- Current Password -->
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700">Current Password</label>
                        <input type="password" 
                               value="••••••••" 
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>

                    <!-- New Password -->
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700">New Password</label>
                        <input type="password" 
                               placeholder="New strong password" 
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>

                    <!-- Confirm New Password -->
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-700">Confirm New Password</label>
                        <input type="password" 
                               placeholder="Repeat new password" 
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all">
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-md shadow-indigo-100 transition-colors">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- ================= RIGHT COLUMN: NOTIFICATIONS & DISPLAY (4 Cols) ================= -->
        <div class="col-span-12 lg:col-span-4 space-y-6">

            <!-- Card 1: Notifications Settings -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-bullhorn text-indigo-600"></i>
                    <h2>Notifications</h2>
                </div>

                <div class="space-y-4">
                    <!-- Toggle 1: Course Announcements -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Course Announcements</h4>
                            <p class="text-[11px] text-slate-400">Updates from lecturers</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Toggle 2: Assignment Deadlines -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Assignment Deadlines</h4>
                            <p class="text-[11px] text-slate-400">24h & 1h reminders</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Toggle 3: Grade Published -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Grade Published</h4>
                            <p class="text-[11px] text-slate-400">When new grades are out</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Toggle 4: Forum Replies -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Forum Replies</h4>
                            <p class="text-[11px] text-slate-400">Mentions and thread replies</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Card 2: Display Preference -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm border-b border-slate-100 pb-3">
                    <i class="fa-solid fa-palette text-indigo-600"></i>
                    <h2>Display</h2>
                </div>

                <p class="text-xs text-slate-500">Choose your preferred interface theme.</p>

                <!-- Theme Selection Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Light Theme Option (Selected) -->
                    <button class="p-3 bg-white border-2 border-indigo-600 rounded-xl text-center space-y-1 shadow-sm transition-all">
                        <i class="fa-solid fa-sun text-indigo-600 text-lg"></i>
                        <span class="block text-xs font-bold text-slate-800">Light</span>
                    </button>

                    <!-- Dark Theme Option -->
                    <button class="p-3 bg-slate-50 border border-slate-200 hover:bg-slate-100 rounded-xl text-center space-y-1 transition-all">
                        <i class="fa-regular fa-moon text-slate-400 text-lg"></i>
                        <span class="block text-xs font-medium text-slate-600">Dark</span>
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection