<?php

use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;

it('should fail if incoming email has origin', function () {
    expect(fn() => Email::create(
        account_id: 'acc1',
        from: 'a@b.com',
        to: ['c@d.com'],
        subject: 'Sub',
        body: 'Body',
        direction: Direction::INCOMING,
        folder_id: 'f1',
        attachments: false,
        origin: Origin::MANUAL, // Incoming shouldn't have origin
        read: true,
        read_at: new \DateTime()
    ))->toThrow(\InvalidArgumentException::class, 'Incoming emails cannot have an origin.');
});
