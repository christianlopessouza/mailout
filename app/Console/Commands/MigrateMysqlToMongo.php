<?php

namespace App\Console\Commands;

use App\Domain\Enums\EmailDirectionEnum;
use App\Domain\Enums\EmailFolderEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMysqlToMongo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-mysql-to-mongo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migração do MySQL para o MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('mysql')
            ->table('email')
            ->whereNotNull('to_email')
            ->where('to_email', '!=', '')
            ->whereNotNull('from_email')
            ->where('from_email', '!=', '')
            ->orderBy('id')
            ->chunk(1000, function ($emails) {
                $entities = [];
                foreach ($emails as $email) {
                    $id = $email->id;
                    $from = $email->from_email;
                    $to = [$email->to_email];
                    $cc = [$email->cc_email];
                    $bcc = [];
                    $attachments = [];
                    $subject = $email->assunto;
                    $body = $email->texto;
                    $processedAt = $email->data_insert;
                    $status = $email->status;
                    $emailDirectionEnum;
                    $emailFolderEnum;
                    $isDeleted;
                    $readAt;
                    $isRead;

                    if ($status == 0) {
                        // nao lido
                        $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
                        $emailFolderEnum = EmailFolderEnum::INBOX;
                        $isDeleted = false;
                        $readAt = null;
                        $isRead = false;
                    } else if ($status == 1) {
                        // lido
                        $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
                        $emailFolderEnum = EmailFolderEnum::INBOX;
                        $isDeleted = false;
                        $readAt = null;
                        $isRead = true;

                    } else if ($status == 2) {
                        // lixo
                        $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
                        $emailFolderEnum = EmailFolderEnum::TRASH;
                        $isDeleted = false;
                        $readAt = null;
                        $isRead = true;
                    } else if ($status == 3) {
                        // excluido
                        $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
                        $emailFolderEnum = EmailFolderEnum::TRASH;
                        $isDeleted = true;
                        $readAt = null;
                        $isRead = true;
                    } else if ($status == 4) {
                        //email enviado (@superestagios.com.br) 
                        $emailDirectionEnum = EmailDirectionEnum::SENT;
                        $emailFolderEnum = EmailFolderEnum::SENT;
                        $isDeleted = false;
                        $readAt = null;
                        $isRead = false;
                    } else {

                        if (str_contains($to[0], "@superestagios.com.br")) {
                            $emailDirectionEnum = EmailDirectionEnum::SENT;
                            $emailFolderEnum = EmailFolderEnum::SENT;
                            $isDeleted = false;
                            $readAt = null;
                            $isRead = false;
                        } else {
                            $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
                            $emailFolderEnum = EmailFolderEnum::INBOX;
                            $isDeleted = false;
                            $readAt = null;
                            $isRead = false;
                        }
                    }


                    $entities[] = [
                        'id' => $id,
                        'from' => $from,
                        'to' => $to,
                        'cc' => $cc,
                        'bcc' => $bcc,
                        'attachments' => $attachments,
                        'subject' => $subject,
                        'body' => $body,
                        'threadId' => null,
                        'processedAt' => $processedAt,
                        'received' => $emailDirectionEnum,
                        'folder' => $emailFolderEnum,
                        'isDeleted' => $isDeleted,
                        'readAt' => $readAt,
                        'isRead' => $isRead,
                    ];
                }
                $this->info("Inserindo " . count($entities) . " registros...");
                DB::connection('mongodb')->table('emails')->insert($entities);
            });

        $this->info('Migração concluída com sucesso!');
    }
}
