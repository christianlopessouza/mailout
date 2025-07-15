<?php

namespace App\Infrastructure\Services;

use App\Domain\Entities\Attachment;
use App\Infrastructure\Services\AttachmentService;
use App\Util\File;
use Aws\S3\S3Client;

class S3AttachmentService implements AttachmentService
{
    public function __construct(
        private readonly S3Client $s3Client
    ) {}

    public function get(Attachment $attachment): string
    {
        $file_extension = pathinfo($attachment->getFilename(), PATHINFO_EXTENSION);
        $result = $this->s3Client->getObject([
            'Bucket' => $_ENV['AWS_BUCKET'],
            'Key'    => $_ENV['AWS_ATTACHMENTS_PATH'] . '/' . File::customFileName(
                $attachment->getAttachableId(),
                $attachment->getFilename()
            )
        ]);

        $temp_path = tempnam(sys_get_temp_dir(), 'attach_');
        if ($temp_path === false) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        $stream = $result['Body'];
        file_put_contents($temp_path, $stream);

        return $temp_path;
    }

    public function store(string $filepath, Attachment $attachment): void
    {
        $key = $attachment->getFilename();

        if (!file_exists($filepath)) {
            throw new \RuntimeException("File not found: {$filepath}");
        }

        $this->s3Client->putObject([
            'Bucket'      => $_ENV['AWS_BUCKET'],
            'Key'         => $_ENV['AWS_ATTACHMENTS_PATH'] . '/' . File::customFileName(
                $attachment->getAttachableId(),
                $attachment->getFilename()
            ),
            'SourceFile'  => $filepath,
            'ContentType' => $attachment->getMimeType(),
            'ACL'         => 'private',
        ]);
    }
}
