<?

namespace App\Infrastructure\Persistence\Facades;

use App\Data\EmailFilter;
use App\Domain\Entities\Email;
use App\Infrastructure\Persistence\EmailRepository;
use Illuminate\Support\Facades\DB;

class FacadesEmailRepository implements EmailRepository
{
    public function save(Email $email): void
    {
        DB::table('emails')->insert([
            'id' => $email->getId(),
            'from' => $email->getFrom(),
            'to' => $email->getTo(),
            'cc' => $email->getCc(),
            'bcc' => $email->getBcc(),
            'subject' => $email->getSubject(),
            'body' => $email->getBody(),
            'direction' => $email->getDirection()->value,
            'read' => $email->getRead(),
            'folder_id' => $email->getFolderId(),
            'attachments' => $email->getAttachments(),
            'thread_id' => $email->getThreadId(),
            'processed_at' => $email->getProcessedAt(),
            'read_at' => $email->getReadAt()
        ]);
    }
    public function list(EmailFilter $filter): array
    {
        throw new \Exception('Not implemented');
    }
}
