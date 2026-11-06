<?php

use App\DTO\Kanban\TextSnapshotDTO;

it('returns null for null input', function () {
    expect(TextSnapshotDTO::from(null))->toBeNull();
});

it('keep short text intact', function () {
    $snapshot = TextSnapshotDTO::from('short');

    expect($snapshot->length)->toBe(5)
        ->and($snapshot->preview)->toBe('short');
});

it('truncate long text and stores the real length', function () {
    $snapshot = TextSnapshotDTO::from(str_repeat('a', 500));

    expect($snapshot->length)->toBe(500)
        ->and(mb_strlen($snapshot->preview))->toBe(TextSnapshotDTO::PREVIEW_LIMIT + 1);
});

it('serializes to the activity payload shape', function () {
    expect(TextSnapshotDTO::from('text')->toArray())
        ->toBe(['length' => 4, 'preview' => 'text']);
});
