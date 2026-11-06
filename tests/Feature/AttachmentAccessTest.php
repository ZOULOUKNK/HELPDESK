<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function ticketWithAttachment(User $client): Attachment
    {
        $this->actingAs($client)->post(route('tickets.store'), [
            'title' => 'Facture illisible',
            'description' => 'Voir le document joint, merci.',
            'files' => [UploadedFile::fake()->create('facture.pdf', 20, 'application/pdf')],
        ]);

        return Attachment::firstOrFail();
    }

    public function test_upload_goes_to_private_disk(): void
    {
        $attachment = $this->ticketWithAttachment(User::factory()->create());

        Storage::disk('local')->assertExists($attachment->path);
        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_owner_and_support_can_download(): void
    {
        $client = User::factory()->create();
        $attachment = $this->ticketWithAttachment($client);
        $agent = User::factory()->role(User::ROLE_AGENT)->create();

        $this->actingAs($client)->get(route('attachments.download', $attachment))
            ->assertOk()->assertDownload('facture.pdf');
        $this->actingAs($agent)->get(route('attachments.download', $attachment))->assertOk();
    }

    public function test_other_client_and_guest_cannot_download(): void
    {
        $attachment = $this->ticketWithAttachment(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('attachments.download', $attachment))->assertForbidden();

        auth()->logout();
        $this->get(route('attachments.download', $attachment))->assertRedirect(route('login'));
    }

    public function test_client_cannot_download_internal_note_attachment(): void
    {
        $client = User::factory()->create();
        $agent = User::factory()->role(User::ROLE_AGENT)->create();
        $ticket = Ticket::create([
            'reference' => 'TKT-INT-1',
            'user_id' => $client->id,
            'title' => 'Serveur lent',
            'description' => 'Le serveur répond lentement.',
        ]);

        $this->actingAs($agent)->post(route('tickets.reply', $ticket), [
            'body' => 'Note interne : journaux du serveur.',
            'is_internal' => '1',
            'files' => [UploadedFile::fake()->create('journaux.txt', 5, 'text/plain')],
        ]);

        $attachment = Attachment::firstOrFail();
        $this->actingAs($client)->get(route('attachments.download', $attachment))->assertForbidden();
        $this->actingAs($agent)->get(route('attachments.download', $attachment))->assertOk();
    }

    public function test_files_uploaded_before_the_change_are_still_served(): void
    {
        $client = User::factory()->create();
        $attachment = $this->ticketWithAttachment($client);
        Storage::disk('public')->put($attachment->path, Storage::disk('local')->get($attachment->path));
        Storage::disk('local')->delete($attachment->path);

        $this->actingAs($client)->get(route('attachments.download', $attachment))->assertOk();
    }
}
