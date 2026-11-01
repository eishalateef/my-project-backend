<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'KHAAM POSHAK',
            'slug' => 'clothing',
            'store_type' => 'clothing',
            'theme_key' => 'theme-clothing',
            'is_active' => true,
        ]);
    }

    public function test_can_subscribe_valid_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'artisan.lover@example.com',
            'source' => 'website_footer',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 'subscribed',
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'artisan.lover@example.com',
            'status' => 'subscribed',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_duplicate_email_handled_gracefully(): void
    {
        NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'repeat.visitor@example.com',
            'status' => 'subscribed',
        ]);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'repeat.visitor@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'already_subscribed',
            ]);

        $this->assertEquals(1, NewsletterSubscriber::where('email', 'repeat.visitor@example.com')->count());
    }

    public function test_invalid_email_fails_validation(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $emptyResponse = $this->postJson('/api/newsletter/subscribe', [
            'email' => '',
        ]);

        $emptyResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_list_and_search_subscribers(): void
    {
        NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'customer1@example.com',
            'status' => 'subscribed',
        ]);
        NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'customer2@example.com',
            'status' => 'subscribed',
        ]);

        $response = $this->getJson('/api/admin/newsletter/subscribers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ]);

        $searchResponse = $this->getJson('/api/admin/newsletter/subscribers?search=customer1');
        $searchResponse->assertStatus(200);
        $data = $searchResponse->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('customer1@example.com', $data[0]['email']);
    }

    public function test_admin_can_toggle_subscriber_status(): void
    {
        $sub = NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'toggle.test@example.com',
            'status' => 'subscribed',
        ]);

        $response = $this->patchJson("/api/admin/newsletter/subscribers/{$sub->id}/toggle-status");
        $response->assertStatus(200);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $sub->id,
            'status' => 'unsubscribed',
        ]);

        $responseRevert = $this->patchJson("/api/admin/newsletter/subscribers/{$sub->id}/toggle-status");
        $responseRevert->assertStatus(200);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $sub->id,
            'status' => 'subscribed',
        ]);
    }

    public function test_admin_can_delete_subscriber(): void
    {
        $sub = NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'to.delete@example.com',
            'status' => 'subscribed',
        ]);

        $response = $this->deleteJson("/api/admin/newsletter/subscribers/{$sub->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('newsletter_subscribers', [
            'id' => $sub->id,
        ]);
    }

    public function test_admin_can_export_subscribers_csv(): void
    {
        NewsletterSubscriber::create([
            'store_id' => $this->store->id,
            'email' => 'export@example.com',
            'status' => 'subscribed',
        ]);

        $response = $this->get('/api/admin/newsletter/export-csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}
