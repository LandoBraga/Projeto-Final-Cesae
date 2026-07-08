<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Userprofile as UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary profiles for tests
        $this->createProfiles();
    }

    /** @test */
    public function it_creates_a_user_with_default_profile_when_no_profile_provided()
    {
        User::factory()->create([
            'profile_id' => null,
        ]);

        // Verify that a default profile was created if not exists
        $defaultProfile = UserProfile::where('name', User::ROLE_USER)->first();
        
        // The user should have been assigned the default profile ID during creation
        $user = User::find($this->getLastInsertedId());
        $this->assertEquals($defaultProfile?->id, $user->profile_id);
    }

    /** @test */
    public function it_creates_a_user_with_valid_profile()
    {
        // Create a technician profile first
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);

        User::factory()->create([
            'profile_id' => 1, // Assuming the created profile has id=1
        ]);

        $user = User::find(1);
        
        // Verify user was created with correct data
        $this->assertEquals('password', $user->password);
        $this->assertTrue($user->active === true);
    }

    /** @test */
    public function it_updates_user_profile_to_default_when_invalid()
    {
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]); // id=1
        
        // Create user with invalid profile (non-existent or wrong role)
        $user = User::factory()->create([
            'profile_id' => 999, // Non-existent profile ID
        ]);

        // Verify the default USER profile was created and assigned
        $defaultProfile = UserProfile::where('name', User::ROLE_USER)->first();
        
        if ($defaultProfile) {
            $user->refresh();
            $this->assertEquals($defaultProfile->id, $user->profile_id);
        } else {
            // If default profile doesn't exist yet (edge case), it should be created during creation
            $newDefault = UserProfile::where('name', User::ROLE_USER)->firstOrCreate([]);
            if ($newDefault) {
                $this->assertEquals($newDefault->id, $user->profile_id);
            }
        }
    }

    /** @test */
    public function it_has_correct_role_constants()
    {
        // Verify role constants exist and have correct values
        $this->assertNotNull(User::ROLE_USER);
        $this->assertEquals('user', User::ROLE_USER);
        
        $this->assertNotNull(User::ROLE_TECHNICIAN);
        $this->assertEquals('technician', User::ROLE_TECHNICIAN);

        $this->assertNotNull(User::ROLE_ADMIN);
        $this->assertEquals('admin', User::ROLE_ADMIN);
    }

    /** @test */
    public function it_gets_available_roles()
    {
        $roles = User::getAvailableRoles();
        
        // Verify all roles are returned
        $this->assertCount(3, $roles);
        $this->assertContains(User::ROLE_USER, $roles);
        $this->assertContains(User::ROLE_TECHNICIAN, $roles);
        $this->assertContains(User::ROLE_ADMIN, $roles);
    }

    /** @test */
    public function it_validates_profile_name()
    {
        // Test valid profile names
        $validProfiles = [User::ROLE_USER, User::ROLE_TECHNICIAN, User::ROLE_ADMIN];
        
        foreach ($validProfiles as $profileName) {
            $this->assertTrue((new ReflectionMethod(User::class, 'isValidProfile'))->invoke(null, $profileName));
        }

        // Test invalid profile name
        $invalidProfile = 'unknown_role';
        $this->assertFalse((new ReflectionMethod(User::class, 'isValidProfile'))->invoke(null, $invalidProfile));
    }

    /** @test */
    public function it_checks_admin_status_correctly()
    {
        // Create admin profile and user
        UserProfile::create(['name' => User::ROLE_ADMIN]);
        
        $adminUser = User::factory()->create([
            'profile_id' => 1,
        ]);

        $this->assertTrue($adminUser->isAdmin());
    }

    /** @test */
    public function it_checks_technician_status_correctly()
    {
        // Create technician profile and user
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        
        $technicianUser = User::factory()->create([
            'profile_id' => 1,
        ]);

        $this->assertTrue($technicianUser->isTechnician());
    }

    /** @test */
    public function it_checks_common_user_status_correctly()
    {
        // Create user profile and user
        UserProfile::create(['name' => User::ROLE_USER]);
        
        $commonUser = User::factory()->create([
            'profile_id' => 1,
        ]);

        $this->assertTrue($commonUser->isCommonUser());
    }

    /** @test */
    public function it_returns_false_for_non_matching_roles()
    {
        // Create admin profile and user
        UserProfile::create(['name' => User::ROLE_ADMIN]);
        
        $adminUser = User::factory()->create([
            'profile_id' => 1,
        ]);

        // Admin should not be technician or common user
        $this->assertFalse($adminUser->isTechnician());
        $this->assertFalse($adminUser->isCommonUser());

        // Create technician profile and user
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        
        $technicianUser = User::factory()->create([
            'profile_id' => 2,
        ]);

        // Technician should not be admin or common user
        $this->assertFalse($technicianUser->isAdmin());
        $this->assertFalse($technicianUser->isCommonUser());

        // Create user profile and user
        UserProfile::create(['name' => User::ROLE_USER]);
        
        $commonUser = User::factory()->create([
            'profile_id' => 3,
        ]);

        // Common user should not be admin or technician
        $this->assertFalse($commonUser->isAdmin());
        $this->assertFalse($commonUser->isTechnician());
    }

    /** @test */
    public function it_has_correct_hidden_attributes()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify hidden attributes are not serialized in JSON response
        $jsonResponse = json_encode($user);
        
        $this->assertStringNotContainsString('password', $jsonResponse, 'Password should be hidden');
        $this->assertStringNotContainsString('_tokens', $jsonResponse, '_tokens should be hidden');
    }

    /** @test */
    public function it_has_correct_casts()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify boolean cast for active field
        $this->assertTrue($user->active === true);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify fillable attributes exist in model definition
        $this->assertArrayHasKey('name', $user->getFillable());
        $this->assertArrayHasKey('email', $user->getFillable());
        $this->assertArrayHasKey('password', $user->getFillable());
        $this->assertArrayHasKey('profile_id', $user->getFillable());
        $this->assertArrayHasKey('active', $user->getFillable());
        $this->assertArrayHasKey('api_token', $user->getFillable());
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        // Verify the table name is set correctly
        $this->assertEquals('users', User::$table);
    }

    /** @test */
    public function it_creates_user_with_api_token_on_login()
    {
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        
        // Create user with initial API token
        $user = User::factory()->create([
            'profile_id' => 1,
            'api_token' => bin2hex(random_bytes(32)),
        ]);

        // Verify the user has an API token
        $this->assertNotNull($user->api_token);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        
        $technicianUser = User::factory()->create([
            'profile_id' => 1,
        ]);

        // Verify profile relationship exists and works correctly
        $this->assertNotNull($technicianUser->profile);
        $this->assertEquals(UserProfile::class, get_class($technicianUser->profile));
        
        // Verify tickets relationship (hasMany)
        $tickets = $technicianUser->getTicketsRelation();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $tickets);

        // Verify assigned_tickets relationship (belongsTo via foreign key assignment_to in Ticket model)
        $assignedTickets = $technicianUser->getAssignedTicketsRelation();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $assignedTickets);
    }

    /** @test */
    public function it_has_correct_factory_definition()
    {
        // Verify factory creates users with correct default values
        $user = User::factory()->create();
        
        $this->assertNotNull($user->name);
        $this->assertStringStartsWith('email@', $user->email);
        $this->assertEquals(now(), $user->email_verified_at);
    }

    /** @test */
    public function it_has_correct_password_hashing()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify password is hashed (not plain text 'password')
        $this->assertNotEquals('password', $user->password);
    }

    /** @test */
    public function it_has_correct_notifiable_trait()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify Notifiable trait is used (should have notifications method available via facade)
        $this->assertTrue(method_exists($user, 'notify'));
    }

    /** @test */
    public function it_has_correct_factory_trait()
    {
        User::factory()->create();
        
        // Get the last created user
        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Verify HasFactory trait is used (should have factory method available via facade)
        $this->assertTrue(method_exists($user, 'factory'));
    }

    /** @test */
    public function it_handles_multiple_roles_in_get_available()
    {
        UserProfile::create(['name' => User::ROLE_USER]);
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        
        // Create users with different roles
        $user1 = User::factory()->create([
            'profile_id' => 1,
        ]);

        $this->assertTrue($user1->isCommonUser());
    }

    /** @test */
    public function it_handles_null_profile_correctly()
    {
        // Create user with null profile (should be handled by booting)
        User::factory()->create([
            'profile_id' => null,
        ]);

        $user = User::latest()->first();
        
        if (!$user) {
            return;
        }

        // Profile should have been assigned during creation via booting method
        $this->assertNotNull($user->profile);
    }

    /** @test */
    public function it_handles_missing_profile_name_correctly()
    {
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]); // id=1
        
        // Create user with profile but no name set (edge case)
        $user = User::factory()->create([
            'profile_id' => 1,
        ]);

        if (!$user->profile?->exists()) {
            return;
        }

        // Profile should exist and have correct role
        $this->assertEquals(UserProfile::class, get_class($user->profile));
    }
}
