<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    private function getStats()
    {
        return [
            'totalTasks' => Auth::user()->tasks()->count(),
            'pendingTasks' => Auth::user()->tasks()->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('due_date', '>=', Carbon::today());
                })->count(),
            'completedTasks' => Auth::user()->tasks()->where('status', 'completed')->count(),
            'overdueTasks' => Auth::user()->tasks()->where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::today())
                ->count(),
        ];
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $viewMode = $request->query('view', 'dashboard');

        $query = Auth::user()->tasks();

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

        $tasks = $query->latest()->get();
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

    public function searchSuggestions(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }

        $suggestions = Auth::user()->tasks()
            ->where('title', 'like', "%{$q}%")
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

        $task = Auth::user()->tasks()->create($validated);
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

    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

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

    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

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

    public function toggleStatus(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

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