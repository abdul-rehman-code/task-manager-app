<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    private function getStats()
    {
        return [
            'totalTasks' => Task::count(),
            'pendingTasks' => Task::where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('due_date', '>=', Carbon::today());
                })->count(),
            'completedTasks' => Task::where('status', 'completed')->count(),
            'overdueTasks' => Task::where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::today())
                ->count(),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $viewMode = $request->query('view', 'dashboard');

        $query = Task::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'pending') {
            $query->where('status', 'pending')->where(function ($q) {
                $q->whereNull('due_date')->orWhere('due_date', '>=', Carbon::today());
            });
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        } elseif ($status === 'overdue') {
            $query->where('status', 'pending')
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', Carbon::today());
        }

        $tasks = Task::latest()->get();
        $stats = $this->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
                'stats' => $stats,
            ]);
        }

        return view('tasks.index', array_merge([
            'tasks' => $tasks,
            'status' => $status,
            'search' => $search,
            'viewMode' => $viewMode,
        ], $stats));
    }

    /**
     * Return search suggestions for task titles (minimum 3 characters).
     */
    public function searchSuggestions(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }

        $suggestions = Task::where('title', 'like', "%{$q}%")
            ->select('id', 'title', 'status', 'due_date')
            ->limit(6)
            ->get()
            ->map(function ($task) {
                $isOverdue = $task->status === 'pending' && $task->due_date && $task->due_date->isPast();
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $isOverdue ? 'overdue' : $task->status,
                    'due_date' => $task->due_date ? $task->due_date->format('M d, Y') : null,
                ];
            });

        return response()->json($suggestions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && str_word_count($value) > 100) {
                        $fail('Description cannot be longer than 100 words.');
                    }
                },
            ],
            'due_date' => 'required|date',
        ]);

        $task = Task::create($validated);
        $stats = $this->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully!',
                'task' => $task,
                'stats' => $stats,
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && str_word_count($value) > 100) {
                        $fail('Description cannot be longer than 100 words.');
                    }
                },
            ],
            'due_date' => 'required|date',
        ]);

        $task->update($validated);
        $stats = $this->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully!',
                'task' => $task,
                'stats' => $stats,
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $task->delete();
        $stats = $this->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully!',
                'stats' => $stats,
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
    }

    /**
     * Toggle the status of the specified task (pending <-> completed).
     */
    public function toggleStatus(Request $request, Task $task)
    {
        $task->status = $task->status === 'pending' ? 'completed' : 'pending';
        $task->save();
        $stats = $this->getStats();

        $isOverdue = $task->status === 'pending' && $task->due_date && $task->due_date->isPast();
        $formattedStatus = $isOverdue ? 'overdue' : $task->status;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task status updated to ' . ucfirst($task->status) . '!',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'status_formatted' => $formattedStatus,
                ],
                'stats' => $stats,
            ]);
        }

        return back()->with('success', 'Task status updated!');
    }
}