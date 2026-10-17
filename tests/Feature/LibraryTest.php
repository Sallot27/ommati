<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
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

    public function test_telegram_bot_adds_books_from_allowed_chat(): void
    {
        config([
            'services.telegram.bot_token' => 'TEST',
            'services.telegram.webhook_secret' => 'shh',
            'services.telegram.allowed_chat_ids' => ['111'],
        ]);
        Http::fake([
            'api.telegram.org/botTEST/getFile' => Http::response(['ok' => true, 'result' => ['file_path' => 'documents/file_1.pdf']]),
            'api.telegram.org/file/botTEST/*' => Http::response('%PDF-1.4 test'),
            'api.telegram.org/botTEST/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $update = fn ($chat) => ['message' => [
            'message_id' => 5,
            'chat' => ['id' => $chat],
            'caption' => 'رياض الصالحين | النووي | حديث',
            'document' => ['file_id' => 'F1', 'file_unique_id' => 'U1', 'file_name' => 'riyad.pdf', 'file_size' => 1000],
        ]];

        // Wrong secret
        $this->postJson('/telegram/webhook', $update(111))->assertForbidden();

        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'shh'];
        // Stranger: not added
        $this->postJson('/telegram/webhook', $update(999), $headers)->assertOk();
        $this->assertSame(0, Book::count());

        $this->postJson('/telegram/webhook', $update(111), $headers)->assertOk();
        $book = Book::firstOrFail();
        $this->assertSame('رياض الصالحين', $book->title);
        $this->assertSame('النووي', $book->author);
        $this->assertSame('telegram', $book->source);
        Storage::disk('public')->assertExists($book->path);

        // Duplicate is ignored
        $this->postJson('/telegram/webhook', $update(111), $headers)->assertOk();
        $this->assertSame(1, Book::count());
    }
}
