<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => Hash::make('CurrentPassword123!'),
        ]);
    }

    public function test_authenticated_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('my_avatar.png', 400, 400);

        $response = $this->postJson(route('profile.avatar.update'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profile picture updated successfully.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'avatar_url',
            ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->profile_photo);
        Storage::disk('public')->assertExists($this->user->profile_photo);
    }

    public function test_avatar_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->postJson(route('profile.avatar.update'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('temp.jpg');
        $path = $file->store('avatars', 'public');
        $this->user->profile_photo = $path;
        $this->user->save();

        $response = $this->deleteJson(route('profile.avatar.remove'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profile picture removed.',
            ]);

        $this->user->refresh();
        $this->assertNull($this->user->profile_photo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_user_can_change_password_from_header_modal(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson(route('profile.password.update'), [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewSecretPassword2026!',
            'password_confirmation' => 'NewSecretPassword2026!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Your account password has been successfully updated.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword2026!', $this->user->password));
    }
}
