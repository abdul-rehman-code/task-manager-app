@extends('layouts.app')

@section('title', request('view') === 'tasks_only' ? 'TaskManager - My Tasks' : 'TaskManager - Dashboard')

@section('content')

@php
    // Prepare initial tasks array for Alpine reactive state
    $initialTasks = $tasks->map(function($t) {
        $isOverdue = $t->status === 'pending' && $t->due_date && $t->due_date->isPast();
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description ?? '',
            'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
            'status' => $t->status,
            'is_overdue' => $isOverdue,
            'formatted_status' => $isOverdue ? 'overdue' : $t->status,
        ];
    })->toArray();
@endphp

<div class="space-y-6"
    x-data="{
        tasks: @js($initialTasks),
        stats: {
            totalTasks: {{ $totalTasks }},
            pendingTasks: {{ $pendingTasks }},
            completedTasks: {{ $completedTasks }},
            overdueTasks: {{ $overdueTasks }}
        },
        selectedStatus: @js(request('status', 'all')),
        searchQuery: @js(request('search', '')),
        tableSuggestions: [],
        showTableSuggestions: false,
        tableSearchLoading: false,

        showAddModal: false,
        showEditModal: false,
        showDeleteModal: false,
        taskToDelete: null,

        editTask: { id: null, title: '', description: '', due_date: '' },
        addForm: { title: '', description: '', due_date: '', errors: {}, submitting: false },
        editForm: { errors: {}, submitting: false },

        // Count words in text
        countWords(str) {
            if (!str || !str.trim()) return 0;
            return str.trim().split(/\s+/).filter(w => w.length > 0).length;
        },

        // Filter tasks locally by search & status with ZERO page reload!
        get filteredTasks() {
            return this.tasks.filter(task => {
                let matchStatus = true;
                if (this.selectedStatus === 'pending') {
                    matchStatus = task.status === 'pending' && !task.is_overdue;
                } else if (this.selectedStatus === 'completed') {
                    matchStatus = task.status === 'completed';
                } else if (this.selectedStatus === 'overdue') {
                    matchStatus = task.is_overdue;
                }

                const matchSearch = !this.searchQuery || 
                    task.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                    (task.description && task.description.toLowerCase().includes(this.searchQuery.toLowerCase()));

                return matchStatus && matchSearch;
            });
        },

        // Table search auto-suggestions (triggers at 3+ characters)
        fetchTableSuggestions() {
            if (this.searchQuery.trim().length < 3) {
                this.tableSuggestions = [];
                this.showTableSuggestions = false;
                return;
            }
            this.tableSearchLoading = true;
            fetch('{{ route('tasks.search-suggestions') }}?q=' + encodeURIComponent(this.searchQuery))
                .then(res => res.json())
                .then(data => {
                    this.tableSuggestions = data;
                    this.showTableSuggestions = true;
                    this.tableSearchLoading = false;
                })
                .catch(() => { this.tableSearchLoading = false; });
        },

        selectSuggestion(title) {
            this.searchQuery = title;
            this.showTableSuggestions = false;
        },

        // AJAX Add Task (NO FULL PAGE RELOAD)
        submitAddTask() {
            this.addForm.errors = {};

            if (!this.addForm.title || !this.addForm.title.trim()) {
                this.addForm.errors.title = ['Title field is required.'];
            }
            if (!this.addForm.due_date) {
                this.addForm.errors.due_date = ['Due date field is required.'];
            }
            if (this.countWords(this.addForm.description) > 100) {
                this.addForm.errors.description = ['Description cannot be longer than 100 words.'];
            }

            if (Object.keys(this.addForm.errors).length > 0) return;

            this.addForm.submitting = true;
            fetch('{{ route('tasks.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: this.addForm.title,
                    description: this.addForm.description,
                    due_date: this.addForm.due_date
                })
            })
            .then(res => res.json())
            .then(data => {
                this.addForm.submitting = false;
                if (data.errors) {
                    this.addForm.errors = data.errors;
                } else if (data.success) {
                    const newTask = {
                        id: data.task.id,
                        title: data.task.title,
                        description: data.task.description || '',
                        due_date: data.task.due_date ? data.task.due_date.substring(0, 10) : null,
                        status: data.task.status,
                        is_overdue: false,
                        formatted_status: data.task.status
                    };
                    this.tasks.unshift(newTask);
                    if (data.stats) this.stats = data.stats;
                    
                    this.addForm.title = '';
                    this.addForm.description = '';
                    this.addForm.due_date = '';
                    this.showAddModal = false;
                    triggerToast(data.message, 'success');
                }
            })
            .catch(() => {
                this.addForm.submitting = false;
                triggerToast('Failed to create task. Please try again.', 'error');
            });
        },

        // AJAX Edit Task (NO FULL PAGE RELOAD)
        submitEditTask() {
            this.editForm.errors = {};

            if (!this.editTask.title || !this.editTask.title.trim()) {
                this.editForm.errors.title = ['Title field is required.'];
            }
            if (!this.editTask.due_date) {
                this.editForm.errors.due_date = ['Due date field is required.'];
            }
            if (this.countWords(this.editTask.description) > 100) {
                this.editForm.errors.description = ['Description cannot be longer than 100 words.'];
            }

            if (Object.keys(this.editForm.errors).length > 0) return;

            this.editForm.submitting = true;
            fetch(`/tasks/${this.editTask.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: this.editTask.title,
                    description: this.editTask.description,
                    due_date: this.editTask.due_date
                })
            })
            .then(res => res.json())
            .then(data => {
                this.editForm.submitting = false;
                if (data.errors) {
                    this.editForm.errors = data.errors;
                } else if (data.success) {
                    const index = this.tasks.findIndex(t => t.id === this.editTask.id);
                    if (index !== -1) {
                        this.tasks[index].title = data.task.title;
                        this.tasks[index].description = data.task.description || '';
                        this.tasks[index].due_date = data.task.due_date ? data.task.due_date.substring(0, 10) : null;
                    }
                    if (data.stats) this.stats = data.stats;
                    this.showEditModal = false;
                    triggerToast(data.message, 'success');
                }
            })
            .catch(() => {
                this.editForm.submitting = false;
                triggerToast('Failed to update task.', 'error');
            });
        },

        // AJAX Toggle Task Status (NO FULL PAGE RELOAD)
        toggleStatus(taskId) {
            fetch(`/tasks/${taskId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const task = this.tasks.find(t => t.id === taskId);
                    if (task) {
                        task.status = data.task.status;
                        task.formatted_status = data.task.status_formatted;
                        task.is_overdue = data.task.status_formatted === 'overdue';
                    }
                    if (data.stats) this.stats = data.stats;
                    triggerToast(data.message, 'success');
                }
            })
            .catch(() => {
                triggerToast('Failed to toggle status.', 'error');
            });
        },

        // Open Custom Delete Modal
        confirmDelete(task) {
            this.taskToDelete = task;
            this.showDeleteModal = true;
        },

        // AJAX Delete Task (NO FULL PAGE RELOAD)
        executeDelete() {
            if (!this.taskToDelete) return;
            const taskId = this.taskToDelete.id;
            fetch(`/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.tasks = this.tasks.filter(t => t.id !== taskId);
                    if (data.stats) this.stats = data.stats;
                    this.showDeleteModal = false;
                    this.taskToDelete = null;
                    triggerToast(data.message, 'success');
                }
            })
            .catch(() => {
                triggerToast('Failed to delete task.', 'error');
            });
        }
    }"
    @open-add-modal.window="showAddModal = true">

    {{-- CONDITIONAL WELCOME BANNER & STAT CARDS (Hidden when viewMode is 'tasks_only') --}}
    @if ($viewMode !== 'tasks_only')
        
        {{-- WELCOME BANNER --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-blue-600/10 border border-blue-200/60 p-6 lg:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
            <div class="space-y-2 text-center md:text-left z-10">
                <div class="inline-flex items-center gap-2">
                    <span class="text-2xl">👋</span>
                    <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Hello, {{ Auth::user()->name ?? 'Abdul Rehman' }}!
                    </h1>
                </div>
                <p class="text-sm text-slate-600 font-medium">
                    Here's what's happening with your tasks today.
                </p>
            </div>

            <!-- Banner Graphic Illustration -->
            <div class="relative w-48 md:w-56 h-28 flex items-center justify-center shrink-0">
                <div class="w-44 h-24 bg-slate-900 rounded-lg shadow-xl border-4 border-slate-700 p-2 flex flex-col justify-between relative overflow-hidden">
                    <div class="bg-blue-600/20 w-full h-full rounded flex items-center justify-center border border-blue-500/30">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white shadow">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-1 w-52 h-2.5 bg-slate-400 rounded-full shadow"></div>
            </div>
        </div>

        {{-- 4 STAT SUMMARY CARDS GRID --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">↑ 2 new today</span>
                </div>
                <div class="mt-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tasks</span>
                    <h3 class="text-2xl font-bold text-slate-900 mt-0.5" x-text="stats.totalTasks"></h3>
                </div>
            </div>

            <!-- Pending Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">↑ 1 more than yesterday</span>
                </div>
                <div class="mt-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Tasks</span>
                    <h3 class="text-2xl font-bold text-slate-900 mt-0.5" x-text="stats.pendingTasks"></h3>
                </div>
            </div>

            <!-- Completed Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">↑ 1 completed today</span>
                </div>
                <div class="mt-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Completed Tasks</span>
                    <h3 class="text-2xl font-bold text-slate-900 mt-0.5" x-text="stats.completedTasks"></h3>
                </div>
            </div>

            <!-- Overdue Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full">↑ 1 more than yesterday</span>
                </div>
                <div class="mt-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Overdue Tasks</span>
                    <h3 class="text-2xl font-bold text-slate-900 mt-0.5" x-text="stats.overdueTasks"></h3>
                </div>
            </div>
        </div>

    @endif

    {{-- FULL WIDTH TASKS SECTION --}}
    <div class="w-full space-y-4">
        
        <!-- Table Header Bar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">My Tasks</h2>
                <template x-if="selectedStatus !== 'all'">
                    <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-blue-50 text-blue-700" x-text="selectedStatus"></span>
                </template>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Status Filter Dropdown (ZERO PAGE RELOAD via x-model!) -->
                <div class="flex items-center">
                    <select x-model="selectedStatus"
                        class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-blue-500 cursor-pointer">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>

                <!-- Table Search Input with AUTO-SUGGESTION DROPDOWN & REALTIME FILTER -->
                <div class="relative flex-1 sm:w-64" @click.outside="showTableSuggestions = false">
                    <input type="text"
                        x-model="searchQuery"
                        @input.debounce.300ms="fetchTableSuggestions()"
                        @focus="if (searchQuery.trim().length >= 3) showTableSuggestions = true"
                        autocomplete="off"
                        placeholder="Search tasks (3+ letters auto-suggest)..."
                        class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-blue-500 focus:bg-white transition duration-150">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>

                    <!-- Auto-Suggestion Dropdown Popup under Table Search Input -->
                    <div x-show="showTableSuggestions" x-cloak
                        class="absolute left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 z-50 overflow-hidden max-h-60 overflow-y-auto">
                        <div class="px-3 py-1 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[10px] font-semibold text-slate-500 uppercase">
                            <span>Auto Suggestions</span>
                            <span x-text="tableSuggestions.length + ' result(s)'"></span>
                        </div>

                        <template x-if="tableSuggestions.length === 0">
                            <div class="px-3 py-3 text-xs text-slate-500 text-center">
                                No matching tasks found.
                            </div>
                        </template>

                        <template x-for="sug in tableSuggestions" :key="sug.id">
                            <div @click="selectSuggestion(sug.title)"
                                class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b last:border-0 border-slate-100 flex items-center justify-between transition">
                                <span class="text-xs font-semibold text-slate-800" x-text="sug.title"></span>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': sug.status === 'completed',
                                        'bg-amber-100 text-amber-700': sug.status === 'pending',
                                        'bg-rose-100 text-rose-700': sug.status === 'overdue'
                                    }" x-text="sug.status"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="searchQuery || selectedStatus !== 'all'">
                    <button @click="searchQuery = ''; selectedStatus = 'all'" class="text-xs font-semibold text-slate-500 hover:text-slate-700 px-1 py-1 cursor-pointer">Clear</button>
                </template>

                <!-- Add New Task Button -->
                <button @click="showAddModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-md shadow-blue-600/30 transition shrink-0 flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add New Task
                </button>
            </div>
        </div>

        <!-- Task List Table Container -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden w-full">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Title</th>
                            <th class="py-3.5 px-6">Due Date</th>
                            <th class="py-3.5 px-6">Status Tag</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-for="task in filteredTasks" :key="task.id">
                            <tr class="hover:bg-slate-50/60 transition"
                                :class="task.is_overdue ? 'bg-rose-50/30' : (task.status === 'completed' ? 'bg-emerald-50/30' : '')">
                                
                                <!-- Title & Description -->
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-900 text-sm" x-text="task.title"></div>
                                    <template x-if="task.description">
                                        <div class="text-slate-500 text-xs mt-0.5 max-w-xl" x-text="task.description"></div>
                                    </template>
                                </td>

                                <!-- Due Date -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <template x-if="task.due_date">
                                        <div class="flex items-center gap-1.5 font-medium text-xs"
                                            :class="task.is_overdue ? 'text-rose-600 font-bold' : 'text-slate-600'">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span x-text="task.due_date"></span>
                                        </div>
                                    </template>
                                    <template x-if="!task.due_date">
                                        <span class="text-slate-400">—</span>
                                    </template>
                                </td>

                                <!-- Clean Status Tag Pill -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 border-emerald-200/80': task.status === 'completed',
                                            'bg-rose-100 text-rose-800 border-rose-200/80': task.is_overdue,
                                            'bg-amber-100 text-amber-800 border-amber-200/80': task.status === 'pending' && !task.is_overdue
                                        }"
                                        x-text="task.is_overdue ? 'Overdue' : (task.status === 'completed' ? 'Completed' : 'Pending')">
                                    </span>
                                </td>

                                <!-- Action Buttons including Prominent "Mark as Complete" / "Mark as Pending" (AJAX) -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2.5">
                                        
                                        <!-- PROMINENT BUTTON: Mark as Complete / Mark as Pending -->
                                        <template x-if="task.status === 'pending' || task.is_overdue">
                                            <button @click="toggleStatus(task.id)"
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/30 transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Mark as Complete
                                            </button>
                                        </template>

                                        <template x-if="task.status === 'completed'">
                                            <button @click="toggleStatus(task.id)"
                                                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs shadow-md shadow-amber-500/30 transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Mark as Pending
                                            </button>
                                        </template>

                                        <!-- Edit Button -->
                                        <button @click="showEditModal = true; editTask = { id: task.id, title: task.title, description: task.description, due_date: task.due_date }"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl border border-slate-200 hover:border-blue-300 transition cursor-pointer" title="Edit Task">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <!-- Custom Delete Confirmation Trigger -->
                                        <button @click="confirmDelete(task)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl border border-slate-200 hover:border-rose-300 transition cursor-pointer" title="Delete Task">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredTasks.length === 0">
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <span class="text-sm font-medium">No tasks found matching filter.</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Footer count -->
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Showing <strong x-text="filteredTasks.length"></strong> tasks</span>
            </div>
        </div>

    </div>

    {{-- ADD TASK MODAL (WITH REQUIRED DUE DATE & 100 WORDS DESCRIPTION LIMIT) --}}
    <div x-show="showAddModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="showAddModal = false"
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 relative">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800">Add New Task</h3>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitAddTask()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Title <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="addForm.title" required placeholder="e.g. Design landing page"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="addForm.errors.title ? 'border-rose-400 bg-rose-50/50' : ''">
                    <template x-if="addForm.errors.title">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="addForm.errors.title[0]"></p>
                    </template>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-semibold text-slate-600 uppercase">Description</label>
                        <span class="text-[11px] font-medium" :class="countWords(addForm.description) > 100 ? 'text-rose-500 font-bold' : 'text-slate-400'"
                              x-text="countWords(addForm.description) + '/100 words'"></span>
                    </div>
                    <textarea x-model="addForm.description" rows="3" placeholder="Add task description..."
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="addForm.errors.description || countWords(addForm.description) > 100 ? 'border-rose-400 bg-rose-50/50' : ''"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Maximum 100 words allowed.</p>
                    <template x-if="addForm.errors.description">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="addForm.errors.description[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Due Date <span class="text-rose-500">*</span></label>
                    <input type="date" x-model="addForm.due_date" required
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="addForm.errors.due_date ? 'border-rose-400 bg-rose-50/50' : ''">
                    <template x-if="addForm.errors.due_date">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="addForm.errors.due_date[0]"></p>
                    </template>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showAddModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" :disabled="addForm.submitting"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-600/30 flex items-center gap-1.5 cursor-pointer">
                        <template x-if="addForm.submitting">
                            <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        Save Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT TASK MODAL (WITH REQUIRED DUE DATE & 100 WORDS DESCRIPTION LIMIT) --}}
    <div x-show="showEditModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="showEditModal = false"
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 relative">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800">Edit Task</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitEditTask()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Title <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="editTask.title" required
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="editForm.errors.title ? 'border-rose-400 bg-rose-50/50' : ''">
                    <template x-if="editForm.errors.title">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="editForm.errors.title[0]"></p>
                    </template>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-semibold text-slate-600 uppercase">Description</label>
                        <span class="text-[11px] font-medium" :class="countWords(editTask.description) > 100 ? 'text-rose-500 font-bold' : 'text-slate-400'"
                              x-text="countWords(editTask.description) + '/100 words'"></span>
                    </div>
                    <textarea x-model="editTask.description" rows="3"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="editForm.errors.description || countWords(editTask.description) > 100 ? 'border-rose-400 bg-rose-50/50' : ''"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Maximum 100 words allowed.</p>
                    <template x-if="editForm.errors.description">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="editForm.errors.description[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Due Date <span class="text-rose-500">*</span></label>
                    <input type="date" x-model="editTask.due_date" required
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white"
                        :class="editForm.errors.due_date ? 'border-rose-400 bg-rose-50/50' : ''">
                    <template x-if="editForm.errors.due_date">
                        <p class="text-xs text-rose-500 font-semibold mt-1" x-text="editForm.errors.due_date[0]"></p>
                    </template>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" :disabled="editForm.submitting"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-600/30 flex items-center gap-1.5 cursor-pointer">
                        <template x-if="editForm.submitting">
                            <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        Update Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- BEAUTIFUL CUSTOM DELETE CONFIRMATION MODAL --}}
    <div x-show="showDeleteModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.outside="showDeleteModal = false"
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-sm w-full p-6 text-center relative overflow-hidden">
            
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-900">Delete Task</h3>
            <p class="text-xs text-slate-500 mt-1">
                Are you sure you want to delete <span class="font-bold text-slate-800" x-text="taskToDelete ? taskToDelete.title : ''"></span>? This action cannot be undone.
            </p>

            <div class="flex justify-center gap-2 mt-6">
                <button type="button" @click="showDeleteModal = false"
                    class="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" @click="executeDelete()"
                    class="w-full py-2 px-4 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-rose-600/30 transition cursor-pointer">
                    Delete Task
                </button>
            </div>
        </div>
    </div>

</div>

@endsection