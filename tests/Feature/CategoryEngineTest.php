<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Models\CategoryAttribute;
use App\Modules\Categories\Models\CategoryAttributeOption;
use App\Modules\Categories\Services\CategoryManagementService;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_category_tree_hierarchy_and_depth(): void
    {
        $root = Category::where('slug', 'personals')->first();
        $this->assertNotNull($root);
        $this->assertEquals(0, $root->depth);
        $this->assertTrue($root->isRoot());

        // Check Category Groups (depth 1)
        $groups = $root->children;
        $this->assertGreaterThanOrEqual(5, $groups->count());

        $menSeeking = $groups->firstWhere('slug', 'men-seeking');
        $this->assertNotNull($menSeeking);
        $this->assertEquals(1, $menSeeking->depth);
        $this->assertEquals($root->id, $menSeeking->parent_id);

        // Check Subcategories (depth 2)
        $m4w = Category::where('slug', 'men-seeking-women')->first();
        $this->assertNotNull($m4w);
        $this->assertEquals(2, $m4w->depth);
        $this->assertEquals($menSeeking->id, $m4w->parent_id);
        $this->assertEquals('M4W', $m4w->code);

        // Check Ancestors Traversal
        $ancestors = $m4w->ancestors();
        $this->assertCount(2, $ancestors);
        $this->assertEquals('Personals', $ancestors->first()->name);
        $this->assertEquals('Men Seeking', $ancestors->last()->name);

        // Check Descendant IDs
        $rootDescendantIds = $root->descendantIds(true);
        $this->assertContains($root->id, $rootDescendantIds);
        $this->assertContains($menSeeking->id, $rootDescendantIds);
        $this->assertContains($m4w->id, $rootDescendantIds);
    }

    public function test_maximum_depth_enforcement_prevents_runaway_recursion(): void
    {
        $service = app(CategoryManagementService::class);
        $parent = Category::where('slug', 'men-seeking-women')->first(); // depth 2

        // Create depth 3
        $depth3 = $service->createCategory([
            'name' => 'Depth 3 Cat',
            'parent_id' => $parent->id,
        ]);
        $this->assertEquals(3, $depth3->depth);

        // Create depth 4
        $depth4 = $service->createCategory([
            'name' => 'Depth 4 Cat',
            'parent_id' => $depth3->id,
        ]);
        $this->assertEquals(4, $depth4->depth);

        // Create depth 5 (max allowed depth)
        $depth5 = $service->createCategory([
            'name' => 'Depth 5 Cat',
            'parent_id' => $depth4->id,
        ]);
        $this->assertEquals(5, $depth5->depth);

        // Attempting to create depth 6 must throw InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $service->createCategory([
            'name' => 'Depth 6 Cat',
            'parent_id' => $depth5->id,
        ]);
    }

    public function test_category_cannot_be_its_own_parent_or_descendant(): void
    {
        $service = app(CategoryManagementService::class);
        $menSeeking = Category::where('slug', 'men-seeking')->first();
        $m4w = Category::where('slug', 'men-seeking-women')->first();

        // Self as parent
        try {
            $service->updateCategory($menSeeking, ['parent_id' => $menSeeking->id]);
            $this->fail('Expected InvalidArgumentException when setting self as parent.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cannot be its own parent', $e->getMessage());
        }

        // Descendant as parent
        try {
            $service->updateCategory($menSeeking, ['parent_id' => $m4w->id]);
            $this->fail('Expected InvalidArgumentException when setting descendant as parent.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cannot set a descendant category as parent', $e->getMessage());
        }
    }

    public function test_category_slug_uniqueness_and_auto_increment(): void
    {
        $service = app(CategoryManagementService::class);

        $cat1 = $service->createCategory([
            'name' => 'Speed Dating Nights',
            'slug' => 'speed-dating',
        ]);
        $this->assertEquals('speed-dating', $cat1->slug);

        $cat2 = $service->createCategory([
            'name' => 'Speed Dating Singles',
            'slug' => 'speed-dating',
        ]);
        $this->assertEquals('speed-dating-1', $cat2->slug);

        $cat3 = $service->createCategory([
            'name' => 'Speed Dating Third',
            'slug' => 'speed-dating',
        ]);
        $this->assertEquals('speed-dating-2', $cat3->slug);
    }

    public function test_dynamic_attributes_and_inheritance(): void
    {
        $m4w = Category::where('slug', 'men-seeking-women')->first();
        $this->assertNotNull($m4w);

        $allAttributes = $m4w->allAttributes();
        $codes = $allAttributes->pluck('code')->all();

        // Must include global intent and status
        $this->assertContains('intent', $codes);
        $this->assertContains('relationship_status', $codes);

        // Must include category-specific attributes
        $this->assertContains('height_inches', $codes);
        $this->assertContains('smoking', $codes);

        $smokingAttr = $allAttributes->firstWhere('code', 'smoking');
        $this->assertNotNull($smokingAttr);
        $this->assertGreaterThanOrEqual(2, $smokingAttr->options->count());
    }

    public function test_attribute_validation_engine(): void
    {
        $service = app(CategoryService::class);
        $m4w = Category::where('slug', 'men-seeking-women')->first();

        // Valid data
        $validInput = [
            'intent' => 'dating',
            'relationship_status' => 'single',
            'height_inches' => 71,
            'smoking' => 'non_smoker',
            'interests' => 'Live music and cycling',
        ];

        $validated = $service->validateListingAttributes($m4w, $validInput);
        $this->assertEquals('dating', $validated['intent']);
        $this->assertEquals(71, $validated['height_inches']);

        // Invalid: missing required intent
        $invalidInput = [
            'height_inches' => 70,
        ];

        $this->expectException(ValidationException::class);
        $service->validateListingAttributes($m4w, $invalidInput);
    }

    public function test_category_reassignment_service_migrates_listings(): void
    {
        $service = app(CategoryManagementService::class);
        $source = Category::where('slug', 'casual-dating')->first();
        $target = Category::where('slug', 'long-term-dating')->first();

        $user = User::factory()->create(['is_age_verified' => true]);
        $city = City::first();

        // Create a listing in source category
        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $source->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Casual Coffee Connection',
            'slug' => 'casual-coffee-' . Str::random(6),
            'description' => 'Looking for coffee and light conversation.',
            'poster_age' => 28,
            'status' => 'active',
        ]);

        $this->assertEquals($source->id, $listing->category_id);

        // Reassign
        $migratedCount = $service->reassignListings($source, $target, $user->id);
        $this->assertEquals(1, $migratedCount);

        $listing->refresh();
        $this->assertEquals($target->id, $listing->category_id);

        // Verify status history audit
        $this->assertDatabaseHas('listing_status_history', [
            'listing_id' => $listing->id,
            'changed_by_user_id' => $user->id,
        ]);
    }

    public function test_safe_deletion_dependency_checks(): void
    {
        $service = app(CategoryManagementService::class);
        $source = Category::where('slug', 'friendship')->first();
        $target = Category::where('slug', 'activity-partners')->first();

        $user = User::factory()->create();
        $city = City::first();

        // Add listing to friendship category
        Listing::create([
            'user_id' => $user->id,
            'category_id' => $source->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'title' => 'Friendship Meet',
            'slug' => 'friendship-' . Str::random(6),
            'description' => 'Looking for friends to hang out.',
            'poster_age' => 30,
            'status' => 'active',
        ]);

        // canDeleteCategory should return false
        $check = $service->canDeleteCategory($source);
        $this->assertFalse($check['can_delete']);
        $this->assertGreaterThan(0, $check['listings_count']);

        // Attempting to delete without reassignment must fail
        try {
            $service->deleteCategory($source);
            $this->fail('Expected exception when deleting category with active listings.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('without specifying a reassignment target', $e->getMessage());
        }

        // Delete with reassignment should succeed
        $deleted = $service->deleteCategory($source, $target->id, $user->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('categories', ['id' => $source->id]);
    }

    public function test_public_category_directory_and_landing_pages(): void
    {
        // 1. Directory Page
        $response = $this->get('/categories');
        $response->assertStatus(200);
        $response->assertSee('USA Adult Personal Classifieds Directory');
        $response->assertSee('Men Seeking');
        $response->assertSee('Women Seeking');

        // 2. Category Detail Page
        $response = $this->get('/categories/men-seeking-women');
        $response->assertStatus(200);
        $response->assertSee('Men Seeking Women');
        $response->assertSee('M4W');
        $response->assertSee('Category Safety Guidelines');

        // 3. Inactive category should 404 for guests
        $inactiveCat = Category::create([
            'name' => 'Draft Category',
            'slug' => 'draft-cat-inactive',
            'is_active' => false,
        ]);

        $response = $this->get('/categories/draft-cat-inactive');
        $response->assertStatus(404);
    }

    public function test_admin_preview_mode_allows_viewing_disabled_categories(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_age_verified' => true,
        ]);

        $inactiveCat = Category::create([
            'name' => 'Hidden Draft Category',
            'slug' => 'hidden-draft-cat',
            'is_active' => false,
        ]);

        // Guest gets 404
        $this->get('/categories/hidden-draft-cat')->assertStatus(404);

        // Admin accessing preview gets 200 with admin preview banner
        $response = $this->actingAs($admin)->get("/admin/categories/{$inactiveCat->id}/preview");
        $response->assertStatus(200);
        $response->assertSee('ADMIN PREVIEW MODE');
        $response->assertSee('Hidden Draft Category');
    }

    public function test_admin_category_management_routes_require_authorization(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Guest redirect
        $this->get('/admin/categories')->assertRedirect('/login');

        // Regular user forbidden
        $this->actingAs($user)->get('/admin/categories')->assertStatus(403);

        // Admin allowed
        $this->actingAs($admin)->get('/admin/categories')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/categories/create')->assertStatus(200);
    }

    public function test_category_api_endpoints_return_json_tree_and_attributes(): void
    {
        // 1. Category Tree
        $response = $this->getJson('/api/categories/tree');
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'subcategories',
                ],
            ],
        ]);

        // 2. Category Attributes
        $m4w = Category::where('slug', 'men-seeking-women')->first();
        $response = $this->getJson("/api/categories/{$m4w->id}/attributes");
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('category.slug', 'men-seeking-women');
        $response->assertJsonStructure([
            'status',
            'category' => ['id', 'name', 'slug'],
            'attributes' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'field_type',
                    'is_required',
                    'is_filterable',
                    'options',
                ],
            ],
        ]);
    }

    public function test_livewire_category_filter_component(): void
    {
        $comp = Livewire::test(\App\Livewire\CategoryFilter::class);
        $comp->assertStatus(200);

        // Group categories should be loaded
        $comp->assertSet('selectedRootId', fn ($val) => !is_null($val));

        $menSeeking = Category::where('slug', 'men-seeking')->first();
        $comp->call('selectGroup', $menSeeking->id);

        $comp->assertSet('selectedGroupId', $menSeeking->id);
        $comp->assertSet('selectedCategorySlug', 'men-seeking');
        $comp->assertDispatched('category-selected');

        $m4w = Category::where('slug', 'men-seeking-women')->first();
        $comp->call('selectSubcategory', $m4w->id);
        $comp->assertSet('selectedSubcategoryId', $m4w->id);
        $comp->assertSet('selectedCategorySlug', 'men-seeking-women');
    }
}
