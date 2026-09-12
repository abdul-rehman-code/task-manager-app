TASK MANAGER APP (Test)


A simple  Task Manager built with Laravel and Tailwind CSS.
Users can add, edit, delete, and mark tasks as complete/pending, with
filtering by status.


FEATURES

 Create, edit, and delete tasks
 Mark tasks as complete/pending with a single click (toggle)
 Filter tasks by status (All / Pending / Completed)
 Overdue task highlighting (red badge for pending tasks past due date)
 Form validation with inline error messages
 Single-page UI - add/edit handled via modals (no separate pages)
 Live Search with Auto-Suggestions (AJAX search)


TECH STACK

 Laravel (PHP framework)
 MySQL (database) 
 Tailwind CSS (styling, via Vite) & Alpine.js(lightweight JS for modal interactions)



SETUP STEPS


1. Clone the repository

   git clone https://github.com/abdul-rehman-code/task-manager-app.git
   cd task-manager-app

2. Install PHP dependencies

   composer install

3. Install JS dependencies

   npm install

4. Environment setup

   cp .env.example .env
   php artisan key:generate

   Open .env and set your database credentials:

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=task_manager_app
   DB_USERNAME=root
   DB_PASSWORD=

   Create a database named task_manager_app (via phpMyAdmin or CLI)
   before running migrations.

5. Run migrations

   php artisan migrate

6. Build frontend assets

   npm run build

   (or "npm run dev" for local development with hot reload)

7. Start the server

   php artisan serve

8. Visit http://127.0.0.1:8000/ in your browser.

AUTHENTICATION

Authentication is implemented manually via a custom controller
(AuthController.php), without using any package like Breeze,
Jetstream, or Fortify. Laravel's built-in core Auth methods are used
directly:
 
1. AuthController.php (Custom Controller)
 
     Registration (register): User input (name, email, phone,
     password) is validated. The password is securely hashed using
     Hash::make() (bcrypt) before being saved, and the user is
     automatically logged in via Auth::login($user).
 
     Login (login): Auth::attempt(['email' => ..., 'password' => ...])
     is used, with phone also checked as an alternative identifier.
     On successful validation, session()->regenerate() is called to
     prevent session fixation.
 
   - Logout (logout): Auth::logout() clears the session,
     $request->session()->invalidate() invalidates it, and the CSRF
     token is regenerated.
 
2. Middleware Protection (middleware('auth'))
 
   - Task routes in web.php are protected using
     Route::middleware('auth'), so no one can access the dashboard or
     tasks without logging in first.


ASSUMPTIONS MADE


  Single-page UI was used instead of separate create/edit pages, for
  time efficiency - add and edit forms are shown as modals on the same
  tasks/index page.

  Toggle route (PATCH /tasks/{task}/toggle) is used to flip a task's
  status between pending and completed without requiring a full
  edit-form submission.


  Task "status" is stored as an enum (pending, completed) rather than
  a free-text string, for data integrity.

  Overdue is determined purely by comparing due_date to the current
  date for tasks still marked pending - no separate "overdue" status
  is stored in the database.


FOLDER STRUCTURE HIGHLIGHTS


  app/Models/Task.php
  Task model with fillable fields and date casting

  app/Http/Controllers/TaskController.php
  Handles all CRUD + toggle logic

  resources/views/layouts/app.blade.php
  Base layout with Tailwind + Alpine

  resources/views/tasks/index.blade.php
  Main single-page UI (list, filter, add/edit modals)

  routes/web.php
  Resource routes + custom toggle route
