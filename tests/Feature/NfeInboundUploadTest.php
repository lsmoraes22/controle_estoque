<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class NfeInboundUploadTest extends DatabaseTestCase
{
    public function test_authenticated_xml_upload_returns_json_and_uses_a_server_generated_name(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(), 'sanctum');

        $response = $this->postJson('/api/upload/nf/inbound', [
            'file' => UploadedFile::fake()->createWithContent('nota.xml', $this->fixture()),
            'name' => '../../rules.json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'stored');
        $filename = $response->json('file');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.xml$/', $filename);
        Storage::disk('local')->assertExists('xml/nfs/inbound/'.$filename);
        Storage::disk('local')->assertMissing('rules.json');
        $this->assertStringNotContainsString('../', $filename);
        $this->assertStringNotContainsString('\\', $filename);
    }

    public function test_valid_xml_content_is_stored_even_when_original_name_has_another_extension(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(), 'sanctum');

        $response = $this->postJson('/api/upload/nf/inbound', [
            'file' => UploadedFile::fake()->createWithContent('nota.txt', $this->fixture()),
        ]);

        $response->assertCreated()->assertJsonPath('status', 'stored');
        $filename = $response->json('file');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.xml$/', $filename);
        $this->assertSame($this->fixture(), Storage::disk('local')->get('xml/nfs/inbound/'.$filename));
    }

    public function test_repeated_uploads_do_not_overwrite_existing_inbound_files(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(), 'sanctum');

        $first = $this->postJson('/api/upload/nf/inbound', [
            'file' => UploadedFile::fake()->createWithContent('same.xml', '<first/>'),
            'name' => 'same.xml',
        ])->assertCreated();
        $firstPath = 'xml/nfs/inbound/'.$first->json('file');

        $second = $this->postJson('/api/upload/nf/inbound', [
            'file' => UploadedFile::fake()->createWithContent('same.xml', '<second/>'),
            'name' => 'same.xml',
        ])->assertCreated();
        $secondPath = 'xml/nfs/inbound/'.$second->json('file');

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertSame('<first/>', Storage::disk('local')->get($firstPath));
        $this->assertSame('<second/>', Storage::disk('local')->get($secondPath));
    }

    public function test_upload_above_the_configured_size_limit_is_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(), 'sanctum');
        config(['nfe.max_xml_bytes' => 10]);

        $this->postJson('/api/upload/nf/inbound', [
            'file' => UploadedFile::fake()->createWithContent('large.xml', str_repeat('x', 11)),
        ])->assertStatus(413)->assertJsonStructure(['error']);

        $this->assertSame([], Storage::disk('local')->files('xml/nfs/inbound'));
    }

    public function test_missing_file_is_rejected_explicitly(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postJson('/api/upload/nf/inbound')->assertUnprocessable()->assertJsonStructure(['error']);
    }

    private function fixture(): string
    {
        return file_get_contents(base_path('tests/Fixtures/nfe/inbound-ficticia.xml'));
    }
}
