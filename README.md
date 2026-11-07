# Smart Scheduling Logic — نظام إدارة الدراسة والمهام الذكي

A Study & Task Management web application built with **Laravel 13 + Blade**.
Its core is a **smart scheduling engine** that distributes study sessions across
the week automatically, based on subject difficulty, assignment/exam deadlines,
the student's weekly free time, and their logged daily energy level.

تطبيق ويب لإدارة الدراسة والمهام مبني بـ Laravel 13 وBlade. أهم ما فيه **محرك
جدولة ذكي** يوزّع جلسات المذاكرة تلقائياً على أيام الأسبوع حسب صعوبة المواد،
ومواعيد تسليم الواجبات والاختبارات، وأوقات تفرّغ الطالب، ومستوى طاقته اليومية.

---

## Features | الميزات

| Feature | الميزة | Details |
|---|---|---|
| Subjects | المواد الدراسية | CRUD with difficulty (`low` / `medium` / `high`), priority, and color |
| Assignments & Exams | الواجبات والاختبارات | Type (`assignment` / `exam`), due date, `pending` / `completed` status, due-soon & overdue tracking |
| Study Sessions | جلسات الدراسة | Manual scheduling with statuses: `scheduled`, `in_progress`, `completed`, `missed`, `rescheduled` |
| Smart Scheduling | الجدولة الذكية | Automatic weekly plan, auto-rescheduling, conflict detection & resolution |
| Availability | أوقات التفرغ | Weekly free-time windows per day (0 = Sunday … 6 = Saturday) |
| Energy Tracker | تتبع الطاقة | One energy log per day (`low` / `medium` / `high`), used by the scheduler |
| Pomodoro | وقت التركيز | Log focus sessions (1–180 min) per subject |
| Notifications | الإشعارات | Read/unread, mark all as read; created automatically when a session is rescheduled |
| Dashboard | لوحة التحكم | Progress rate, pending vs completed, overdue count, due-soon list, weekly sessions, energy, focus minutes, chart data |
| Auth & Profile | الحساب | Laravel Breeze: register, login, email verification, password reset, profile |

---

## Tech Stack | التقنيات

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Auth | Laravel Breeze (Blade stack) |
| Database | SQLite by default (`.env.example`), MySQL supported |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Tests | PHPUnit 12 |
| Code style | Laravel Pint |

---

## Smart Scheduling Algorithm | خوارزمية الجدولة الذكية

Located in `app/Services/SmartScheduleService.php`.

### 1. Weekly schedule — `generateWeeklySchedule($user, $weekStart = null)`

1. **Subject weight** = `difficulty × 2` (low = 1, medium = 2, high = 3) plus an urgency
   bonus from the nearest pending assignment/exam:
   `+6` if due within 2 days, `+4` within 7 days, `+1` later, `0` if overdue.
2. **Day order** — the 7 days of the week are sorted by the logged energy level
   (high = 3, medium = 2, low = 1; days with no log count as medium).
3. **Energy matching** — on each day, subjects whose difficulty ≤ the day's energy get
   a `+10` bonus, so harder subjects go to higher-energy days.
4. **Slot filling** — every availability window is split into **60-minute** sessions.
   Time already taken by other sessions is removed first (interval subtraction);
   free gaps shorter than 30 minutes are ignored.
5. **Re-generation** — previously generated `scheduled` sessions in that week are
   replaced; completed / in-progress / other sessions are kept. Runs in a DB transaction.

### 2. Reschedule — `rescheduleSession($session)`

Moves a session to the nearest free availability slot within the next **14 days**,
keeping its duration, sets its status to `rescheduled`, and creates a notification.
Returns `null` if no slot is found.

### 3. Resolve conflicts — `resolveConflicts($user)`

Finds upcoming active sessions that overlap each other and reschedules each conflicting
one. Returns `['rescheduled' => n, 'failed' => n]`.

```php
$scheduler = app(\App\Services\SmartScheduleService::class);

$scheduler->generateWeeklySchedule($user);   // توزيع ذكي لأسبوع كامل
$scheduler->rescheduleSession($session);     // نقل جلسة لأقرب وقت متاح
$scheduler->resolveConflicts($user);         // حل جميع التعارضات
```

---

## Database Schema | بنية قاعدة البيانات

```
users ─┬─ subjects ─┬─ assignments_exams
       │            ├─ study_sessions
       │            └─ pomodoro_sessions   (subject optional, null on delete)
       ├─ energy_logs          (unique per user + date)
       ├─ notifications
       └─ availability_slots   (day_of_week 0–6, start_time, end_time)
```

| Table | Main columns |
|---|---|
| `subjects` | `name`, `difficulty_level`, `color`, `priority` |
| `assignments_exams` | `subject_id`, `title`, `description`, `type`, `due_date`, `status` |
| `study_sessions` | `subject_id`, `scheduled_date`, `start_time`, `end_time`, `status`, `notes` |
| `energy_logs` | `date`, `energy_level` |
| `pomodoro_sessions` | `subject_id`, `duration_minutes`, `started_at`, `ended_at` |
| `notifications` | `title`, `message`, `is_read` |
| `availability_slots` | `day_of_week`, `start_time`, `end_time` |

All tables cascade on user deletion. Indexes cover the main queries:
`(user_id, status, due_date)`, `(user_id, scheduled_date)`, `(user_id, is_read)`,
`(user_id, day_of_week)`.

---

## Installation | التثبيت

Requirements: PHP 8.3+, Composer, Node.js & npm.

```bash
git clone https://github.com/AyaAlMohmad/smart_scheduling_logic.git
cd smart_scheduling_logic

composer install
cp .env.example .env
php artisan key:generate

# SQLite (default): create the database file
touch database/database.sqlite
# Or set DB_CONNECTION=mysql and DB_* values in .env

php artisan migrate

npm install
npm run build

php artisan serve
```

Or run everything with one command: `composer run setup`.

Open `http://localhost:8000`, register an account, and verify the email.
All app pages require a logged-in, verified user.

---

## Routes | المسارات

All routes below use the `auth` + `verified` middleware.

| Method | URI | Description |
|---|---|---|
| GET | `/dashboard` | Dashboard & analytics — لوحة التحكم |
| resource | `/subjects` | Subjects CRUD — المواد |
| resource | `/assignments` | Assignments & exams CRUD — الواجبات والاختبارات |
| PATCH | `/assignments/{assignment}/complete` | Mark as completed |
| GET | `/schedule` | Weekly schedule — الجدول الأسبوعي |
| GET / POST | `/schedule/create`, `/schedule` | Create a manual session |
| GET / PUT / DELETE | `/schedule/{session}/edit`, `/schedule/{session}` | Edit / delete a session |
| POST | `/schedule/generate` | Run the smart scheduler (optional `week_start`) |
| POST | `/schedule/{session}/reschedule` | Auto-move a session to the next free slot |
| POST | `/schedule/resolve-conflicts` | Fix all overlapping sessions |
| POST | `/schedule/availability` | Replace weekly availability (`slots[]`) |
| GET / POST / DELETE | `/pomodoro`, `/pomodoro/{pomodoro}` | Focus sessions — وقت التركيز |
| GET / POST / DELETE | `/energy`, `/energy/{log}` | Daily energy log — تتبع الطاقة |
| GET | `/notifications` | Notification center — الإشعارات |
| PATCH | `/notifications/{notification}/read` | Mark one as read |
| POST | `/notifications/read-all` | Mark all as read |
| DELETE | `/notifications/{notification}` | Delete a notification |
| GET / PATCH / DELETE | `/profile` | Profile (Breeze) |

---

## Project Structure | بنية المشروع

```
app/
├── Http/
│   ├── Controllers/   Dashboard, Subject, Assignment, Schedule, Pomodoro,
│   │                  EnergyLog, Notification, Profile (+ Breeze Auth controllers)
│   └── Requests/      Form Requests for validation and ownership checks
│                      (users can only use their own subjects)
├── Models/            Subject, AssignmentExam, StudySession, EnergyLog,
│                      PomodoroSession, AppNotification, AvailabilitySlot, User
└── Services/
    └── SmartScheduleService.php   ← smart scheduling engine
database/migrations/   one migration per table
resources/views/       dashboard, subjects, assignments, schedule,
                       pomodoro, energy, notifications, profile, auth
routes/web.php         application routes
tests/Feature/         StudyAppTest + Breeze auth/profile tests
```

---

## Testing | الاختبارات

```bash
php artisan test
# or
composer test
```

The suite has 38 tests. `tests/Feature/StudyAppTest.php` covers:
subjects, assignments and manual sessions CRUD, validation, ownership rules (a user
cannot use another user's subject), smart schedule generation, rescheduling with
notifications, availability and energy log updates, pomodoro logging, notifications,
dashboard stats, and guest redirects.

---

## Notes for Frontend Developers | ملاحظات لمطوّر الواجهة

- The Blade views are simple starter views. Controllers pass clearly named variables
  to each view — see the comment at the top of `resources/views/dashboard.blade.php`
  for the dashboard data list.
- Dashboard chart data (`labels`, `completed_per_day`, `energy_per_day`,
  `subject_distribution`) is in the `$charts` variable and embedded as JSON in
  `<script id="dashboard-charts" type="application/json">`.
- Views can be replaced freely; routes, controllers, and validation stay the same.

---

## License

MIT
