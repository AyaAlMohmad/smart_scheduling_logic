<?php

namespace Tests\Feature;

use App\Models\AssignmentExam;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyAppTest extends TestCase
{
    use RefreshDatabase;

    private function userWithSubject(): array
    {
        $user = User::factory()->create();
        $subject = $user->subjects()->create([
            'name' => 'Math',
            'difficulty_level' => 'high',
        ]);

        return [$user, $subject];
    }

    public function test_subject_crud(): void
    {
        [$user] = [User::factory()->create()];

        $this->actingAs($user)->post(route('subjects.store'), [
            'name' => 'Physics',
            'difficulty_level' => 'medium',
        ])->assertRedirect(route('subjects.index'));

        $subject = $user->subjects()->firstOrFail();
        $this->assertDatabaseHas('subjects', ['name' => 'Physics', 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('subjects.index'))->assertOk()->assertSee('Physics');
        $this->actingAs($user)->put(route('subjects.update', $subject), [
            'difficulty_level' => 'high',
        ])->assertRedirect(route('subjects.index'));
        $this->assertEquals('high', $subject->fresh()->difficulty_level);

        $this->actingAs($user)->delete(route('subjects.destroy', $subject));
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_subject_validation_rejects_bad_difficulty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('subjects.store'), [
            'name' => 'X',
            'difficulty_level' => 'impossible',
        ])->assertSessionHasErrors('difficulty_level');
    }

    public function test_assignment_crud_and_complete(): void
    {
        [$user, $subject] = $this->userWithSubject();

        $this->actingAs($user)->post(route('assignments.store'), [
            'subject_id' => $subject->id,
            'title' => 'Homework 1',
            'type' => 'assignment',
            'due_date' => now()->addDays(3)->format('Y-m-d H:i'),
        ])->assertRedirect(route('assignments.index'));

        $assignment = $user->assignmentsExams()->firstOrFail();
        $this->actingAs($user)->patch(route('assignments.complete', $assignment));
        $this->assertEquals('completed', $assignment->fresh()->status);
    }

    public function test_cannot_assign_task_to_other_users_subject(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignSubject = $other->subjects()->create(['name' => 'Secret', 'difficulty_level' => 'low']);

        $this->actingAs($user)->post(route('assignments.store'), [
            'subject_id' => $foreignSubject->id,
            'title' => 'Hack',
            'type' => 'exam',
            'due_date' => now()->addDay(),
        ])->assertSessionHasErrors('subject_id');
    }

    public function test_manual_study_session_crud(): void
    {
        [$user, $subject] = $this->userWithSubject();

        $this->actingAs($user)->post(route('schedule.store'), [
            'subject_id' => $subject->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '17:00',
        ])->assertRedirect(route('schedule.index'));

        $this->actingAs($user)->get(route('schedule.index'))->assertOk()->assertSee('Math');
    }

    public function test_smart_schedule_generation(): void
    {
        [$user, $subject] = $this->userWithSubject();

        $monday = now()->startOfWeek();
        $u = $user;
        $u->availabilitySlots()->create(['day_of_week' => $monday->dayOfWeek, 'start_time' => '16:00', 'end_time' => '19:00']);
        $u->energyLogs()->create(['date' => $monday->toDateString(), 'energy_level' => 'high']);
        $u->assignmentsExams()->create([
            'subject_id' => $subject->id,
            'title' => 'Exam',
            'type' => 'exam',
            'due_date' => now()->addDays(4),
        ]);

        $this->actingAs($user)->post(route('schedule.generate'))
            ->assertRedirect(route('schedule.index'));

        $this->assertEquals(3, $user->studySessions()->count()); // 3-hour slot → 3 sessions
        $this->assertEquals($monday->toDateString(), $user->studySessions()->first()->scheduled_date->toDateString());
    }

    public function test_reschedule_moves_session_and_notifies(): void
    {
        [$user, $subject] = $this->userWithSubject();
        $nextWeek = now()->addWeek()->startOfWeek();
        $user->availabilitySlots()->create([
            'day_of_week' => $nextWeek->dayOfWeek,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $session = $user->studySessions()->create([
            'subject_id' => $subject->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->post(route('schedule.reschedule', $session));
        $moved = $session->fresh();
        $this->assertEquals('rescheduled', $moved->status);
        $this->assertEquals($nextWeek->toDateString(), $moved->scheduled_date->toDateString());
        $this->assertEquals(1, $user->appNotifications()->count());
    }

    public function test_availability_upsert(): void
    {
        [$user] = [User::factory()->create()];

        $this->actingAs($user)->post(route('schedule.availability'), [
            'slots' => [
                ['day_of_week' => 1, 'start_time' => '16:00', 'end_time' => '19:00'],
                ['day_of_week' => 3, 'start_time' => '16:00', 'end_time' => '18:00'],
            ],
        ]);
        $this->assertEquals(2, $user->availabilitySlots()->count());
    }

    public function test_energy_log_upsert(): void
    {
        [$user] = [User::factory()->create()];

        $this->actingAs($user)->post(route('energy.store'), [
            'date' => today()->toDateString(),
            'energy_level' => 'high',
        ]);
        $response = $this->actingAs($user)->post(route('energy.store'), [
            'date' => today()->toDateString(),
            'energy_level' => 'low',
        ]);
        $response->assertSessionHasNoErrors();

        $this->assertEquals(1, $user->energyLogs()->count());
        $this->assertEquals('low', $user->energyLogs()->first()->energy_level);
    }

    public function test_pomodoro_log(): void
    {
        [$user, $subject] = $this->userWithSubject();

        $this->actingAs($user)->post(route('pomodoro.store'), [
            'subject_id' => $subject->id,
            'duration_minutes' => 25,
        ]);

        $this->assertEquals(1, $user->pomodoroSessions()->count());
        $this->actingAs($user)->get(route('pomodoro.index'))->assertOk();
    }

    public function test_notifications_mark_all_read(): void
    {
        $user = User::factory()->create();
        $user->appNotifications()->create(['title' => 'Hi', 'message' => 'msg']);
        $user->appNotifications()->create(['title' => 'Hi2', 'message' => 'msg2']);

        $this->actingAs($user)->post(route('notifications.read-all'));
        $this->assertEquals(0, $user->appNotifications()->unread()->count());
    }

    public function test_dashboard_loads_with_stats(): void
    {
        [$user, $subject] = $this->userWithSubject();
        $user->assignmentsExams()->create([
            'subject_id' => $subject->id, 'title' => 'T1', 'type' => 'assignment',
            'due_date' => now()->addDays(2), 'status' => 'completed',
        ]);
        $user->assignmentsExams()->create([
            'subject_id' => $subject->id, 'title' => 'T2', 'type' => 'exam',
            'due_date' => now()->addDays(5),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('T2')
            ->assertSee('50'); // 1 of 2 completed = 50% progress rate
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('subjects.index'))->assertRedirect(route('login'));
        $this->get(route('schedule.index'))->assertRedirect(route('login'));
    }
}
