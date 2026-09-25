<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TicketController;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    public function test_ticket_attachment_upload_replaces_old_file(): void
    {
        Storage::fake('public');

        $oldPath = 'lampiran_tiket/old-attachment.jpg';
        Storage::disk('public')->put($oldPath, 'old-content');

        $ticket = new Ticket();
        $ticket->lampiran = $oldPath;

        $request = Request::create('/api/tickets/1', 'PUT');
        $request->files->add([
            'lampiran' => UploadedFile::fake()->image('new-attachment.jpg'),
        ]);

        $controller = new TicketController();
        $method = new \ReflectionMethod(TicketController::class, 'handleLampiranUpload');
        $method->setAccessible(true);

        $validated = [];
        $args = [$request, $ticket, &$validated];
        $newPath = $method->invokeArgs($controller, $args);

        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);
        $this->assertStringStartsWith('lampiran_tiket/', $newPath);
        $this->assertArrayHasKey('lampiran', $validated);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }
}
