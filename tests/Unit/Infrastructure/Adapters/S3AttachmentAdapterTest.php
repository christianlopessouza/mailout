<?php

use App\Infrastructure\Adapters\S3AttachmentAdapter;
use App\Domain\Entities\Attachment;
use Aws\S3\S3Client;
use Mockery;

it('should store file in s3', function () {
    // Setup
    $s3Client = Mockery::mock(S3Client::class);
    $attachment = Mockery::mock(Attachment::class);
    $attachment->shouldReceive('getFilename')->andReturn('test.txt');
    $attachment->shouldReceive('getAttachableId')->andReturn('id123');
    $attachment->shouldReceive('getMimeType')->andReturn('text/plain');
    
    $adapter = new S3AttachmentAdapter($s3Client);
    $filepath = tempnam(sys_get_temp_dir(), 'test_');
    file_put_contents($filepath, 'hello world');
    
    // Expectations
    $s3Client->shouldReceive('putObject')
        ->once()
        ->with(Mockery::on(function ($args) use ($filepath) {
            return $args['Bucket'] === $_ENV['AWS_BUCKET'] && $args['SourceFile'] === $filepath;
        }));
    
    // Execution
    $adapter->store($filepath, $attachment);
    
    // Cleanup
    unlink($filepath);
});
