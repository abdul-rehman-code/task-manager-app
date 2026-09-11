@extends('layouts.app')

@section('content')

    {{-- Success message --}}
    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div x-data="{ showAddModal: false, showEditModal: false, editTask: {} }">

        {{-- Top bar: Filter + Add Task button --}}
        <div class="flex justify-between items-center mb-6">
            <form method="GET" action="{{ route('tasks.index') }}">
                <select name="status" onchange="this.form.submit()"
                    class="border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </form>

            <button @click="showAddModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                + Add Task
            </button>
        </div>

        {{-- Task List --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">Title</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">Due Date</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr class="border-b last:border-0">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $task->title }}</div>
                                @if ($task->description)
                                    <div class="text-sm text-gray-500">{{ $task->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($task->due_date)
                                    <span class="{{ $task->due_date->isPast() && $task->status === 'pending' ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                        {{ $task->due_date->format('d M, Y') }}
                                        @if ($task->due_date->isPast() && $task->status === 'pending')
                                            (Overdue)
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($task->status === 'completed')
                                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Completed</span>
                                @else
                                    <span class="bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-1 rounded-full">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                {{-- Toggle status --}}
                                <form action="{{ route('tasks.toggle', $task) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="text-sm text-indigo-600 hover:underline">
                                        {{ $task->status === 'pending' ? 'Mark Complete' : 'Mark Pending' }}
                                    </button>
                                </form>

                                {{-- Edit --}}
                                <button
                                    @click="showEditModal = true; editTask = {
                                        id: {{ $task->id }},
                                        title: @js($task->title),
                                        description: @js($task->description),
                                        due_date: @js(optional($task->due_date)->format('Y-m-d'))
                                    }"
                                    class="text-sm text-blue-600 hover:underline">
                                    Edit
                                </button>

                                {{-- Delete --}}
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Delete this task?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                No tasks found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Add Task Modal --}}
        <div x-show="showAddModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" @click.outside="showAddModal = false">
                <h2 class="text-xl font-semibold mb-4">Add New Task</h2>

                <form action="{{ route('tasks.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @error('title')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @error('due_date')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Task Modal --}}
        <div x-show="showEditModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6" @click.outside="showEditModal = false">
                <h2 class="text-xl font-semibold mb-4">Edit Task</h2>

                <form :action="`/tasks/${editTask.id}`" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" x-model="editTask.title"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" x-model="editTask.description"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" x-model="editTask.due_date"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            Update Task
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection