<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TaskManager - Dashboard')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen"
    x-data="{
        sidebarOpen: false,
        showGlobalAddModal: false,
        toast: { show: false, message: '', type: 'success' },
        triggerToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        }
    }">

    <div x-show="toast.show" x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-2xl border p-4 flex items-center gap-3.5"
        :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50/95 text-emerald-900' : 'border-rose-200 bg-rose-50/95 text-rose-900'">
        
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
            :class="toast.type === 'success' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-rose-600 text-white shadow-md shadow-rose-600/30'">
            <template x-if="toast.type === 'success'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </template>
            <template x-if="toast.type === 'error'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </template>
        </div>

        <div class="flex-1">
            <h4 class="text-xs font-bold uppercase tracking-wider" x-text="toast.type === 'success' ? 'Success' : 'Error'"></h4>
            <p class="text-xs font-medium mt-0.5" x-text="toast.message"></p>
        </div>

        <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div class="flex h-screen overflow-hidden">

        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden transition-opacity"></div>

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col justify-between transition-transform duration-300 ease-in-out shrink-0 border-r border-slate-800 shadow-xl">
            
            <div class="flex flex-col h-full">
                <div class="px-6 py-6 flex items-center justify-between border-b border-slate-800/80">
                    <a href="{{ route('tasks.index') }}" class="flex items-center">
                        <span class="text-xl font-extrabold text-white tracking-tight">TaskManager</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="px-4 py-6 space-y-1.5 flex-1 overflow-y-auto">
                    <a href="{{ route('tasks.index') }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition duration-150 {{ request()->routeIs('tasks.index') && request('view') != 'tasks_only' && request('status') != 'completed' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('tasks.index', ['view' => 'tasks_only']) }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition duration-150 {{ request('view') === 'tasks_only' && request('status') != 'completed' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        My Tasks
                    </a>

                    <button @click="showGlobalAddModal = true"
                        class="w-full flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition duration-150 text-left">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Add Task
                    </button>

                    <a href="{{ route('tasks.index', ['status' => 'completed', 'view' => 'tasks_only']) }}"
                        class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition duration-150 {{ request('status') === 'completed' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Completed
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="pt-4 border-t border-slate-800/60 mt-4">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition duration-150 text-left">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </button>
                    </form>
                </nav>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

            <header class="bg-white border-b border-slate-200 sticky top-0 z-30 px-4 lg:px-8 py-3.5 flex items-center justify-between gap-4">
                
                <div class="flex items-center gap-3 flex-1 max-w-xl">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <div class="relative flex-1" x-data="{
                        query: '',
                        suggestions: [],
                        open: false,
                        loading: false,
                        fetchSuggestions() {
                            if (this.query.trim().length < 3) {
                                this.suggestions = [];
                                this.open = false;
                                return;
                            }
                            this.loading = true;
                            fetch('{{ route('tasks.search-suggestions') }}?q=' + encodeURIComponent(this.query))
                                .then(res => res.json())
                                .then(data => {
                                    this.suggestions = data;
                                    this.open = true;
                                    this.loading = false;
                                })
                                .catch(() => { this.loading = false; });
                        }
                    }" @click.outside="open = false">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text"
                                x-model="query"
                                @input.debounce.300ms="fetchSuggestions()"
                                @focus="if (query.trim().length >= 3) open = true"
                                autocomplete="off"
                                placeholder="Search tasks by title (type at least 3 letters)..."
                                class="w-full pl-10 pr-10 py-2 bg-slate-100 border border-slate-200 text-slate-800 text-xs sm:text-sm rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition duration-150">
                            
                            <template x-if="loading">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </template>
                        </div>

                        <div x-show="open" x-cloak
                            class="absolute left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-50 overflow-hidden">
                            <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                <span>Task Auto-Suggestions</span>
                                <span x-text="suggestions.length + ' result(s)'"></span>
                            </div>

                            <template x-if="suggestions.length === 0">
                                <div class="px-4 py-4 text-xs text-slate-500 text-center">
                                    No tasks found matching "<span x-text="query" class="font-medium text-slate-700"></span>"
                                </div>
                            </template>

                            <template x-for="item in suggestions" :key="item.id">
                                <a :href="'{{ route('tasks.index') }}?search=' + encodeURIComponent(item.title)"
                                    class="block px-4 py-2.5 hover:bg-blue-50 transition border-b last:border-0 border-slate-100 group">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold text-slate-800 group-hover:text-blue-600" x-text="item.title"></span>
                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': item.status === 'completed',
                                                'bg-amber-100 text-amber-700': item.status === 'pending',
                                                'bg-rose-100 text-rose-700': item.status === 'overdue'
                                            }" x-text="item.status"></span>
                                    </div>
                                    <template x-if="item.due_date">
                                        <span class="text-[11px] text-slate-400 block mt-0.5" x-text="'Due: ' + item.due_date"></span>
                                    </template>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-slate-100 transition">
                            <div class="w-9 h-9 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm shadow">
                                {{ strtoupper(substr(Auth::user()->name ?? 'User', 0, 2)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="text-xs font-bold text-slate-800 block leading-tight">{{ Auth::user()->name ?? 'Abdul Rehman' }}</span>
                                <span class="text-[10px] text-slate-400 font-medium block">User</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="userMenuOpen" x-cloak
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-800">{{ Auth::user()->name ?? 'Abdul Rehman' }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 font-semibold flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div x-show="showGlobalAddModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="showGlobalAddModal = false"
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800">Add New Task</h3>
                <button @click="showGlobalAddModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="$dispatch('trigger-global-add')" class="space-y-4">
                <p class="text-xs text-slate-500">Use the main dashboard form or click Save Task below to create a new task.</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showGlobalAddModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="button" @click="showGlobalAddModal = false; $dispatch('open-add-modal')"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-600/30">
                        Open Task Form
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>