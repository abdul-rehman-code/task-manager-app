<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TaskManager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Background Gradient Accents -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/30 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl shadow-2xl p-8 relative z-10">
        <!-- Logo & Header (No Icon Badge & No Subtitle Text!) -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">TaskManager</h1>
            <h2 class="text-sm font-medium text-slate-400 mt-2">Sign in to your account</h2>
        </div>

        <!-- Flash Messages (Auto-dismisses after 3 seconds!) -->
        @if (session('success'))
            <div x-data="{ showMsg: true }"
                x-init="setTimeout(() => showMsg = false, 3000)"
                x-show="showMsg"
                x-cloak
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mb-4 p-3 bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 text-sm font-medium rounded-xl text-center shadow">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Login Input (Email or Phone) -->
            <div>
                <label for="login" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email or Phone Number</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                        placeholder="email@example.com or phone"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-900/80 border border-slate-700 text-slate-100 placeholder-slate-500 text-sm rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition duration-150">
                </div>
                @error('login')
                    <p class="mt-1 text-xs text-rose-400 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input id="password" type="password" name="password" required
                        placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-900/80 border border-slate-700 text-slate-100 placeholder-slate-500 text-sm rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition duration-150">
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-rose-400 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex items-center text-xs text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 bg-slate-900 border-slate-700 rounded text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                    <span class="ml-2">Remember me</span>
                </label>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition duration-200 mt-6 active:scale-[0.99] cursor-pointer">
                Sign In
            </button>
        </form>

        <!-- Footer Link -->
        <p class="text-center text-sm text-slate-400 mt-6">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-blue-400 hover:text-blue-300 transition hover:underline">Create Account</a>
        </p>
    </div>
</body>
</html>
