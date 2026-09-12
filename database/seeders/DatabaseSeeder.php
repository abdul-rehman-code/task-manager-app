<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default user
        $user = User::firstOrCreate(
            ['email' => 'abdul@example.com'],
            [
                'name' => 'Abdul Rehman',
                'phone' => '+92 300 1234567',
                'password' => Hash::make('password'),
            ]
        );

        // Clear existing tasks to ensure clean demo state
        Task::truncate();

        $tasks = [
            [
                'title' => 'Complete project documentation',
                'description' => 'Write and finalize the project documentation for the client.',
                'due_date' => '2025-09-15',
                'status' => 'pending',
            ],
            [
                'title' => 'Design landing page',
                'description' => 'Create a modern and responsive landing page UI.',
                'due_date' => '2025-09-18',
                'status' => 'pending',
            ],
            [
                'title' => 'Fix login issue',
                'description' => 'Resolve the login bug reported by the user.',
                'due_date' => '2025-09-10',
                'status' => 'pending', // Overdue because date is in the past
            ],
            [
                'title' => 'Update dependencies',
                'description' => 'Update Laravel and other packages to the latest version.',
                'due_date' => '2025-09-12',
                'status' => 'completed',
            ],
            [
                'title' => 'Prepare for meeting',
                'description' => 'Prepare slides for the client meeting.',
                'due_date' => '2025-09-20',
                'status' => 'pending',
            ],
            [
                'title' => 'Plan next sprint',
                'description' => 'Discuss with team and plan next sprint tasks.',
                'due_date' => '2025-09-22',
                'status' => 'pending',
            ],
            [
                'title' => 'Design mobile version',
                'description' => 'Make the application responsive for mobile devices.',
                'due_date' => '2025-09-25',
                'status' => 'pending',
            ],
            [
                'title' => 'Client feedback revisions',
                'description' => 'Implement changes based on client feedback.',
                'due_date' => '2025-09-28',
                'status' => 'pending',
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
