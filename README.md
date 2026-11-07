# Smart Scheduling Logic — نظام إدارة الدراسة والمهام الذكي

A comprehensive Study & Task Management web application built with **Laravel + Blade**.
The centerpiece is a **smart scheduling engine** that automatically distributes study
sessions across the week based on subject difficulty, assignment/exam deadlines,
weekly availability, and the student's logged daily energy level.

تطبيق ويب متكامل لإدارة الدراسة والمهام مبني بـ Laravel وBlade. يتميز النظام
بمحرك جدولة ذكي يوزع جلسات المذاكرة تلقائياً على أيام الأسبوع حسب صعوبة المواد،
مواعيد تسليم الواجبات والاختبارات، أوقات التفرغ، ومستوى طاقة الطالب اليومية.

---

## Features | الميزات

- **Subjects (المواد الدراسية)** — CRUD with difficulty levels (low / medium / high) and priority.
- **Assignments & Exams (الواجبات والاختبارات)** — deadlines, pending/completed status, due-soon tracking.
- **Smart Scheduling (الجدولة الذكية)** — automatic weekly distribution of study sessions:
  - hard subjects land on high-energy days,
  - subjects with near deadlines get priority weighting,
  - generated sessions never overlap existing ones (interval subtraction),
  - auto-rescheduling to the next free slot when conflicts happen,
  - conflict detection & bulk resolution across the week.
- **Study Sessions (جلسات الدراسة)** — manual scheduling + statuses (scheduled, in_progress, completed, missed, rescheduled).
- **Energy Tracker (تتبع الطاقة)** — one log per day; feeds the scheduling algorithm.
- **Pomodoro Focus (وقت التركيز)** — log focused work sessions per subject.
- **Notifications (الإشعارات)** — read/unread states, generated automatically on reschedules.
- **Dashboard (لوحة التحكم)** — progress rate, pending vs completed tasks, weekly schedule summary, energy trend, focus minutes, and chart-ready JSON analytics.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12, PHP 8.3 |
| Auth | Laravel Breeze (Blade stack) |
| Database | MySQL (works on SQLite for dev/tests) |
| Views | Blade (stub views ready for frontend integration) |
| Tests | PHPUnit — 38 feature tests |

---

## Database Schema | بنية قاعدة البيانات

```
users ─┬─ subjects ─┬─ assignments_exams
       │            ├─ study_sessions
       │            └─ pomodoro_sessions
       ├─ energy_logs        (unique per user+date)
       ├─ notifications
       └─ availability_slots (weekly free-time windows, day_of_week 0–6)
```

All tables cascade on user/subject deletion, with indexes on the hot query paths
(`user_id + status + due_date`, `user_id + scheduled_date`, `user_id + is_read`).

---

## Smart Scheduling Algorithm | خوارزمية الجدولة

`app/Services/SmartScheduleService.php`

1. **Subject weight** = `difficulty (1–3) × 2` + urgency bonus (+6 if due ≤ 2 days, +4 if ≤ 7 days).
2. **Day ordering** — days are sorted by the user's logged `energy_level` (high → low).
3. **Matching** — on each day, subjects whose difficulty ≤ the day's energy weight get a +10 match bonus, so hard subjects are scheduled on high-energy days first.
4. **Slot filling** — each availability window is split into 60-minute sessions; occupied intervals (manual/completed sessions) are subtracted via interval arithmetic.
5. **Conflict resolution** — overlapping sessions are detected and moved to the next free availability slot (up to 14 days ahead), and a notification is created.

```php
$service->generateWeeklySchedule($user);          // توزيع ذكي لأسبوع كامل
$service->rescheduleSession($session);            // نقل جلسة لأقرب وقت متاح
$service->resolveConflicts($user);                // حل جميع التعارضات
```

---

## Installation | التثبيت

```bash
git clone https://github.com/AyaAlMohmad/smart_scheduling_logic.git
cd smart_scheduling_logic

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

# Configure your database in .env (MySQL or sqlite), then:
php artisan migrate

php artisan serve
```

Then register an account — everything lives under `/dashboard` and requires auth.

---

## Main Routes | أهم المسارات

| Method | URI | Description |
|---|---|---|
| GET | `/dashboard` | Student overview + analytics |
| resource | `/subjects` | Subjects CRUD |
| resource | `/assignments` | Assignments/exams CRUD + `PATCH {id}/complete` |
| GET/POST | `/schedule` | Weekly schedule view & manual session creation |
| POST | `/schedule/generate` | Run the smart scheduler |
| POST | `/schedule/{id}/reschedule` | Auto-move a session |
| POST | `/schedule/resolve-conflicts` | Fix all overlapping sessions |
| POST | `/schedule/availability` | Replace weekly availability windows |
| GET/POST | `/pomodoro` | Focus session log |
| GET/POST | `/energy` | Daily energy tracker |
| GET | `/notifications` | Notification center |

---

## Project Structure | بنية المشروع

```
app/
├── Http/
│   ├── Controllers/   SubjectController, AssignmentController, ScheduleController,
│   │                  DashboardController, PomodoroController,
│   │                  NotificationController, EnergyLogController
│   └── Requests/      10 Form Request classes (validation + ownership checks)
├── Models/            Subject, AssignmentExam, StudySession, EnergyLog,
│                      PomodoroSession, AppNotification, AvailabilitySlot
└── Services/
    └── SmartScheduleService.php   ← the smart scheduling engine
```

---

## Testing | الاختبار

```bash
php artisan test        # 38 tests, 104 assertions
```

`tests/Feature/StudyAppTest.php` covers CRUD flows, validation, authorization
boundaries (users can't attach tasks to other users' subjects), the smart
scheduler end-to-end, rescheduling + notifications, energy upsert, and the
dashboard stats.

---

## Notes for the Frontend Developer

- Every controller returns Blade views with clearly-named variables; each stub
  view documents the data it receives (see `dashboard.blade.php` for the full list).
- The dashboard embeds chart-ready data in `<script id="dashboard-charts" type="application/json">`.
- Replace stub views freely — controllers/routes/validation are stable contracts.

## License

MIT
