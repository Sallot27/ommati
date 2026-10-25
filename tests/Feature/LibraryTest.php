<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\Library;
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

    public function test_reading_progress_bookmarks_and_cover_are_per_person(): void
    {
        $book = app(Library::class)->storeContents('%PDF-1.4 test', 'riyad.pdf', ['title' => 'رياض الصالحين']);
        $this->actingAs($a = User::readerNamed('أم محمد'));

        $this->get("/library/{$book->id}/read")->assertOk()->assertSee('data-needs-pdf', false);
        $this->get("/library/{$book->id}/stream")->assertOk();
        $this->postJson("/library/{$book->id}/progress", ['page' => 37, 'pages' => 200])->assertNoContent();
        $this->assertSame(200, $book->fresh()->pages);
        $this->get('/library')->assertSee('أكملي القراءة')->assertSee('توقفتِ عند صفحة');
        $this->get("/library/{$book->id}")->assertSee('أكملي من صفحة');

        $bm = $this->postJson("/library/{$book->id}/bookmarks", ['page' => 12, 'note' => 'فائدة'])->assertOk()->json();
        $this->get("/library/{$book->id}")->assertSee('فائدة');

        // Cover generated in the browser is stored once and then served.
        $this->post("/library/{$book->id}/cover", ['cover' => UploadedFile::fake()->image('c.jpg', 200, 300)])->assertNoContent();
        $this->assertNotNull($book->fresh()->cover_path);
        $this->get("/library/{$book->id}/cover")->assertOk();

        // Another person has their own place and bookmarks.
        $this->actingAs(User::readerNamed('سارة'));
        $this->get('/library')->assertDontSee('أكملي القراءة');
        $this->deleteJson("/library/{$book->id}/bookmarks/{$bm['id']}")->assertNotFound();
        $this->actingAs($a);
        $this->deleteJson("/library/{$book->id}/bookmarks/{$bm['id']}")->assertNoContent();
    }

    public function test_non_pdf_books_save_place_by_hand(): void
    {
        $book = app(Library::class)->storeContents('epub', 'kitab.epub', ['title' => 'كتاب']);
        $this->actingAs(User::readerNamed('أم محمد'));
        $this->get("/library/{$book->id}")->assertSee('مكان التوقف');
        $this->post("/library/{$book->id}/progress", ['page' => 15, 'note' => 'باب الصبر'])->assertRedirect();
        $this->get("/library/{$book->id}")->assertSee('باب الصبر');
        $this->get("/library/{$book->id}/read")->assertNotFound();
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
