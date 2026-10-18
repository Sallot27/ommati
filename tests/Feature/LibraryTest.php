<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\Telegram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_admin_area_requires_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_and_upload_books(): void
    {
        $admin = User::factory()->create(['email' => 'a@a.test', 'password' => 'secret123', 'is_admin' => true]);
        $this->post('/admin/login', ['email' => 'a@a.test', 'password' => 'secret123'])->assertRedirect('/admin');

        $this->post('/admin/books', [
            'files' => [UploadedFile::fake()->create('riyad_assalihin.pdf', 200, 'application/pdf'), UploadedFile::fake()->create('fiqh-alsunnah.pdf', 100, 'application/pdf')],
            'category' => 'حديث',
        ])->assertRedirect('/admin/books');

        $this->assertSame(2, Book::count());
        $book = Book::where('title', 'riyad assalihin')->firstOrFail();
        Storage::disk('public')->assertExists($book->path);

        $this->get('/library')->assertSee('riyad assalihin');
        $this->get("/library/{$book->id}")->assertOk();
        $this->get("/library/{$book->id}/file")->assertOk();

        $this->actingAs($admin)->delete("/admin/books/{$book->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($book->path);
    }

    public function test_telegram_bot_adds_books_after_admin_approval(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.webhook_secret' => null]);
        $sent = [];
        Http::fake(function ($request) use (&$sent) {
            $url = $request->url();
            if (str_contains($url, 'sendMessage')) {
                $sent[] = $request['chat_id'];

                return Http::response(['ok' => true, 'result' => []]);
            }
            if (str_contains($url, 'getFile')) {
                return Http::response(['ok' => true, 'result' => ['file_path' => 'documents/f.pdf']]);
            }

            return Http::response('%PDF-1.4 test');
        });

        $secret = app(Telegram::class)->secret();
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => $secret];
        $post = fn ($chat, $type, $unique, $caption = null) => [$type === 'channel' ? 'channel_post' : 'message' => [
            'message_id' => 5,
            'chat' => ['id' => $chat, 'type' => $type, 'title' => 'مكتبتي'],
            'caption' => $caption,
            'document' => ['file_id' => 'F'.$unique, 'file_unique_id' => $unique, 'file_name' => 'riyad_assalihin.pdf', 'file_size' => 1000],
        ]];

        // Wrong secret
        $this->postJson('/telegram/webhook', $post(-100123, 'channel', 'U1'))->assertForbidden();

        // Unknown channel: nothing added, nothing posted in the channel, shows as pending
        $this->postJson('/telegram/webhook', $post(-100123, 'channel', 'U1'), $headers)->assertOk();
        $this->assertSame(0, Book::count());
        $this->assertSame([], $sent);
        $this->assertArrayHasKey('-100123', app(Telegram::class)->pendingChats());

        // Admin approves from the panel
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post('/admin/telegram/approve', ['chat_id' => '-100123'])->assertRedirect();

        // Channel post with a long description: added silently, title from file name
        $this->postJson('/telegram/webhook', $post(-100123, 'channel', 'U1', "كتاب رائع\nللإمام النووي\nحمّل الآن"), $headers)->assertOk();
        $book = Book::firstOrFail();
        $this->assertSame('riyad assalihin', $book->title);
        $this->assertSame('telegram', $book->source);
        Storage::disk('public')->assertExists($book->path);
        $this->assertSame([], $sent);

        // Duplicate ignored
        $this->postJson('/telegram/webhook', $post(-100123, 'channel', 'U1'), $headers)->assertOk();
        $this->assertSame(1, Book::count());

        // Private chat: needs approval too, then replies
        $this->postJson('/telegram/webhook', $post(555, 'private', 'U2', 'رياض الصالحين | النووي | حديث'), $headers)->assertOk();
        $this->assertSame(1, Book::count());
        $this->post('/admin/telegram/approve', ['chat_id' => '555']);
        $this->postJson('/telegram/webhook', $post(555, 'private', 'U2', 'رياض الصالحين | النووي | حديث'), $headers)->assertOk();
        $this->assertSame('النووي', Book::latest('id')->first()->author);
        $this->assertContains('555', $sent);
    }
}
