<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an owner can upload and remove a verification document', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'ssm',
            'document' => UploadedFile::fake()->create('ssm.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

    $doc = $venue->documents()->first();
    expect($doc)->not->toBeNull()
        ->and($doc->type)->toBe('ssm')
        ->and($doc->original_name)->toBe('ssm.pdf');
    Storage::disk('local')->assertExists($doc->path);

    $this->actingAs($venue->owner)
        ->delete(route('owner.venues.documents.destroy', [$venue, $doc]))
        ->assertRedirect();

    expect($venue->documents()->count())->toBe(0);
    Storage::disk('local')->assertMissing($doc->path);
});

test('a stranger cannot upload a document to someone elses venue', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();
    $other = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($other)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'ssm',
            'document' => UploadedFile::fake()->create('ssm.pdf', 50, 'application/pdf'),
        ])->assertForbidden();
});

test('an invalid document type is rejected', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)
        ->post(route('owner.venues.documents.store', $venue), [
            'type' => 'totally_made_up',
            'document' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('type');
});

test('only the venue owner and an admin can view a document', function () {
    Storage::fake('local');
    $venue = Venue::factory()->create();

    $this->actingAs($venue->owner)->post(route('owner.venues.documents.store', $venue), [
        'type' => 'ssm',
        'document' => UploadedFile::fake()->create('ssm.pdf', 30, 'application/pdf'),
    ]);
    $doc = $venue->documents()->first();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $stranger = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($venue->owner)->get(route('venue-documents.show', $doc))->assertOk();
    $this->actingAs($admin)->get(route('venue-documents.show', $doc))->assertOk();
    $this->actingAs($stranger)->get(route('venue-documents.show', $doc))->assertForbidden();
});

test('a guest is sent to login when opening a document', function () {
    $venue = Venue::factory()->create();
    $doc = $venue->documents()->create([
        'type' => 'ssm', 'path' => 'venue-documents/x.pdf', 'original_name' => 'ssm.pdf',
    ]);

    $this->get(route('venue-documents.show', $doc))->assertRedirect();
});

/** Upload a document row for each required verification type (no real files needed). */
function uploadAllDocs(Venue $venue): void
{
    foreach (Venue::verificationKeys() as $type) {
        $venue->documents()->create([
            'type' => $type, 'path' => "venue-documents/{$type}.pdf", 'original_name' => "{$type}.pdf",
        ]);
    }
}

test('My Venues prompts the owner to upload documents when any are missing', function () {
    $venue = Venue::factory()->pending()->create();

    $this->actingAs($venue->owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee('Upload documents');
});

test('the upload-documents prompt clears once every document is provided', function () {
    $venue = Venue::factory()->pending()->create();
    uploadAllDocs($venue);

    $this->actingAs($venue->owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee('Pending approval')
        ->assertDontSee('Upload documents');
});

test('the owner dashboard tells them to upload verification documents to go live', function () {
    $venue = Venue::factory()->pending()->create();

    $this->actingAs($venue->owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Upload your verification documents to go live');
});
