<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Organization;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Room $room;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->organization = Organization::create([
            'name'      => 'IT Division',
            'shortname' => 'IT',
        ]);

        $this->admin = User::factory()->create([
            'role'            => 'admin',
            'organization_id' => $this->organization->id,
        ]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create([
            'role'            => 'user',
            'organization_id' => $this->organization->id,
        ]);
        $this->user->assignRole('user');

        $this->room = Room::create([
            'name'        => 'Ruang Rapat Utama',
            'floor'       => 'Lantai 2',
            'capacity'    => 10,
            'status'      => 'active',
            'description' => 'Fasilitas TV & Projector',
        ]);
    }

    public function test_non_admin_cannot_access_admin_endpoints()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/rooms');

        $response->assertStatus(403);
    }

    public function test_admin_cannot_delete_own_account()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$this->admin->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
    }

    public function test_weekend_booking_is_rejected()
    {
        // Saturday
        $saturday = '2026-08-08';

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'room_id'      => $this->room->id,
                'title'        => 'Rapat Akhir Pekan',
                'start_date'   => $saturday,
                'end_date'     => $saturday,
                'start_hour'   => '09:00',
                'finish_hour'  => '11:00',
                'requester_id' => $this->user->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_schedule_conflict_is_prevented()
    {
        // Monday
        $monday = '2026-08-10';

        // Approved booking
        Book::create([
            'room_id'      => $this->room->id,
            'requester_id' => $this->admin->id,
            'pic_name'     => $this->admin->name,
            'title'        => 'Rapat Direksi',
            'start_date'   => $monday,
            'end_date'     => $monday,
            'start_hour'   => '09:00',
            'finish_hour'  => '11:00',
            'status'       => 'approved',
        ]);

        // Attempt overlapping booking
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'room_id'      => $this->room->id,
                'title'        => 'Rapat Divisi',
                'start_date'   => $monday,
                'end_date'     => $monday,
                'start_hour'   => '10:00',
                'finish_hour'  => '12:00',
                'requester_id' => $this->user->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Ruangan sudah dipesan pada waktu tersebut.',
            ]);
    }

    public function test_public_tv_display_endpoint_is_accessible()
    {
        $response = $this->getJson("/api/v1/display/rooms/{$this->room->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'room',
                    'current_meeting',
                    'next_meetings',
                ],
            ]);
    }
}
