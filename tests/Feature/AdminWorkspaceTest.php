<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_control_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $this->actingAs($admin)->get(route('admin.workspace'))->assertOk();
        $this->actingAs($candidate)->get(route('admin.workspace'))->assertForbidden();
    }

    public function test_admin_can_manage_taxonomy_and_actions_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Healthcare'])->assertSessionHasNoErrors();
        $category = JobCategory::where('name', 'Healthcare')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.created', 'auditable_id' => $category->id]);
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertSessionHasNoErrors();
    }

    public function test_admin_cannot_suspend_or_demote_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['status' => 'suspended'])->assertUnprocessable();
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => 'candidate'])->assertUnprocessable();
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('active', $admin->fresh()->status);
    }
}
