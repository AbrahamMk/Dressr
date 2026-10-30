<?php

namespace Tests\Feature;

use App\Models\Measurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MeasurementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'measured_on' => now()->toDateString(),
            'height_cm' => 175,
            'weight_kg' => 70,
            'chest_cm' => 95,
            'waist_cm' => 80,
            'hips_cm' => 98,
        ], $overrides);
    }

    public function test_measurements_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/measurements')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Measurements/Index'));
    }

    public function test_measurements_page_requires_authentication(): void
    {
        $this->get('/measurements')->assertRedirect('/login');
    }

    public function test_measurement_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/measurements', $this->payload());

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('measurements.index'));

        $this->assertDatabaseCount('measurements', 1);

        $measurement = $user->currentMeasurement();

        $this->assertNotNull($measurement);
        $this->assertEquals(175.0, (float) $measurement->height_cm);
        $this->assertEquals(80.0, (float) $measurement->waist_cm);
    }

    public function test_optional_fields_may_be_omitted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/measurements', [
                'measured_on' => now()->toDateString(),
                'height_cm' => 175,
                'chest_cm' => 95,
                'waist_cm' => 80,
                'hips_cm' => 98,
            ])
            ->assertSessionHasNoErrors();

        $measurement = $user->currentMeasurement();

        $this->assertNull($measurement->weight_kg);
        $this->assertNull($measurement->inseam_cm);
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/measurements', [])
            ->assertSessionHasErrors(['measured_on', 'height_cm', 'chest_cm', 'waist_cm', 'hips_cm']);

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_out_of_range_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/measurements', $this->payload([
                'height_cm' => 300,
                'chest_cm' => 10,
                'measured_on' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['height_cm', 'chest_cm', 'measured_on']);
    }

    public function test_waist_far_larger_than_hips_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/measurements', $this->payload(['waist_cm' => 200, 'hips_cm' => 100]))
            ->assertSessionHasErrors('waist_cm');
    }

    public function test_measurement_can_be_updated_by_its_owner(): void
    {
        $user = User::factory()->create();
        $measurement = Measurement::factory()->for($user)->create(['waist_cm' => 80]);

        $response = $this->actingAs($user)->patch(
            route('measurements.update', $measurement),
            $this->payload(['waist_cm' => 78])
        );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('measurements.index'));

        $this->assertEquals(78.0, (float) $measurement->refresh()->waist_cm);
    }

    public function test_user_cannot_update_another_users_measurement(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $measurement = Measurement::factory()->for($owner)->create(['waist_cm' => 80]);

        $this->actingAs($intruder)
            ->patch(route('measurements.update', $measurement), $this->payload(['waist_cm' => 60]))
            ->assertForbidden();

        $this->assertEquals(80.0, (float) $measurement->refresh()->waist_cm);
    }

    public function test_history_is_ordered_newest_first(): void
    {
        $user = User::factory()->create();

        Measurement::factory()->for($user)->create(['measured_on' => '2024-01-01', 'waist_cm' => 70]);
        $latest = Measurement::factory()->for($user)->create(['measured_on' => '2025-06-01', 'waist_cm' => 75]);
        Measurement::factory()->for($user)->create(['measured_on' => '2024-09-01', 'waist_cm' => 72]);

        $this->assertSame($latest->id, $user->currentMeasurement()->id);

        $this->actingAs($user)
            ->get('/measurements')
            ->assertInertia(fn (Assert $page) => $page
                ->has('measurements.data', 3)
                ->where('measurements.data.0.id', $latest->id)
                ->where('current.id', $latest->id));
    }

    public function test_dashboard_shows_the_current_measurement(): void
    {
        $user = User::factory()->create();
        $measurement = Measurement::factory()->for($user)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('currentMeasurement.id', $measurement->id));
    }
}
