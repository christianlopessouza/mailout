<?php

namespace App\Console\Commands;

use App\Data\EmailTokens;
use App\Domain\Entities\Account;
use App\Domain\Entities\Email;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Util\UUID;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DBMigrateCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-mysql-to-postogree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migração do MySQL para o PostGreeSQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountRepository = new FacadesAccountRepository();
        $emailRepository = new FacadesEmailRepository();

        $email_arquivo_morto = DB::connection('mysqlSMAIL')->select(
            "SELECT e.*,ec.email as email_conta
            FROM email_arquivo_morto e
            LEFT JOIN email_cont ec ON ec.id = e.id_conta
            ORDER BY e.id ASC
            LIMIT 5

            "
        );

        foreach ($email_arquivo_morto as $email) {
            $email_from = $email->from_email;

            $verificaSuperMail = DB::connection('pgsql')
                ->table('emails')
                ->where('external_id', '=', $email->id)
                ->first();

            if (!$verificaSuperMail) {
                if (strpos($email_from, 'superestagios.com.br') !== false) {
                    $direction = Direction::OUTGOING;
                } else {
                    $direction = Direction::INCOMING;
                }


                if (!$email->email_conta) {
                    if (strpos($email->from_email, 'superestagios.com.br') !== false) {
                        $email->email_conta = $email->from_email;
                    } else {
                        $email->email_conta = 'no-account@superestagios.com.br';
                    }
                }

                // verifica se a conta existe
                $verificaEmailConta = DB::connection('pgsql')
                    ->table(table: 'accounts')
                    ->select('id')
                    ->where('email_address', '=', $email->email_conta)
                    ->first();

                if (!$verificaEmailConta) {
                    $data_email = (int) new DateTime($email->data_email)->format('Uv');
                    $account = Account::create(
                        id: UUID::v7(),
                        email_address: $email->email_conta,
                        password: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                        host: 'mail.gruposuper.com.br',
                        port: 587,
                        token: UUID::v4(),
                        username: null
                    );
                    $accountRepository->save($account);
                    $id_conta = $account->getId();
                } else {
                    $id_conta = $verificaEmailConta->id;
                }

                if (!$email_from) {
                    // echo "AQUI DEU ERRO no @FROM $email->id.\n";
                    continue;
                }

                $email_to = preg_split('/[;,\-\s]+/', $email->to_email, -1, PREG_SPLIT_NO_EMPTY);

                if (!count($email_to)) {
                    if ($direction == Direction::INCOMING) {
                        $email_to = [$email_from];
                    } else {
                        $email_to = [];
                    }
                }

                $email_cc = preg_split('/[;,\-\s]+/', $email->cc_email, -1, PREG_SPLIT_NO_EMPTY);

                if (!count($email_cc)) {
                    $email_cc = null;
                }

                $lido = true;
                if ($email->status == 0) {
                    $lido = false;
                }

                $origin = null;

                if ($direction == Direction::INCOMING) {
                    $folder = '6c97bbc3-9869-49dd-a018-1329e83f6afa'; // INBOX
                } else if ($email->modulo && $email->id_requisitado || $email->status == 5) {
                    $origin = Origin::TRANSACTION;
                    $lido = null;
                    $folder = '5341a90d-2e22-4949-ace8-6b1ba1ce64a8'; // TRANSACTION
                } else {
                    $folder = '2901a670-cf1d-493b-8b1b-e080ec097faf'; // SENT
                    $origin ??= Origin::MANUAL;
                    $lido = null;
                }

                if ($email->status == 2) {
                    $folder = '216ddda2-38a2-4881-a4db-4cbf9d6d13ba'; // TRASH
                }

                $deleted = false;
                if ($email->status == 3) {
                    $deleted = true;
                }

                if ($lido == true) {
                    $data_lido = new DateTime('2000-01-01 00:00:00');
                } else {
                    $data_lido = null;
                }

                $data_email = (int) new DateTime($email->data_email)->format('Uv');

                $email_smail = Email::create(
                    id: uuidv7_from_timestamp($data_email),
                    account_id: $id_conta,
                    from: $email_from,
                    to: $email_to,
                    cc: $email_cc,
                    bcc: null,
                    subject: $email->assunto,
                    body: $email->texto,
                    direction: $direction,
                    folder_id: $folder,
                    read: $lido,
                    read_at: $data_lido,
                    deleted: $deleted,
                    processed_at: new DateTime($email->data_email),
                    origin: $origin,
                    external_id: $email->id,
                    attachments: false
                );
                $emailRepository->save($email_smail);

                $emailTokens = new EmailTokens(
                    email_id: $email_smail->getid(),
                    from: $email_from,
                    to: $email_to,
                    cc: $email_cc,
                    bcc: [],
                    subject: $email->assunto,
                    body: $email->texto
                );

                $emailRepository->saveToken($emailTokens);

                $token = $email_smail->getid();
            } else {
                $token = $verificaSuperMail->id;
            }


            $verificaExistencia = DB::connection('pgsql')
                ->table('email_complements')
                ->where('email_id', '=', $email->id)  // Assumindo que você vai usar o ID do email
                ->first();

            if (!$verificaExistencia) {
                // Preparando os dados para salvar no formato JSONB
                $complementData = [
                    'cod_encadeado' => $email->cod_encadeado,
                    'data_email' => $email->data_email,
                    'respondido' => $email->respondido,
                    'status' => $email->status,
                    'resposta' => $email->resposta,
                    'data_resposta' => $email->data_resposta,
                    'resolvido' => $email->resolvido,
                    'controle_interno' => $email->controle_interno,
                    'atualizado' => $email->atualizado,
                    'quem_confirmo_exclusao' => $email->quem_confirmo_exclusao,
                    'quem_respondeu' => $email->quem_respondeu,
                    'id_quem_respondeu' => $email->id_quem_respondeu,
                    'copia' => $email->copia,
                    'exige_resposta' => $email->exige_resposta,
                    'id_requisitado' => $email->id_requisitado,
                    'modulo' => $email->modulo,
                    'problema' => $email->problema,
                    'importante' => $email->importante,
                    'id_controle' => $email->id_controle,
                    'id_categoria' => $email->id_categoria,
                    'data_insert' => $email->data_insert,  // Pode ser agora ou data gerada do banco
                ];

                // Inserindo os dados na tabela com o campo JSONB
                DB::connection('pgsql')->table('email_complements')->insert([
                    'email_id' => $email->id,  // Relacionamento com a tabela emails
                    'complement_data' => json_encode($complementData),  // Salvando como JSONB
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $client = DB::connection('pgsql')
                ->table('clients')  // Tabela de clientes
                ->where('id', '=', $email->id_cliente)  // Aqui você pode usar qualquer critério para recuperar o cliente
                ->first();  // Pega o primeiro cliente ou pode ajustar conforme sua necessidade

            if (!$client) {
                $this->error('Client not found.');
                return;
            }

            $clientId = $client->id;  // A variável client_id agora está definida

            $verificaExistenciaTemplate = DB::connection('pgsql')
                ->table('email_complements_template')
                ->where('client_id', '=', $clientId)  // Verifica se o template já existe para o cliente
                ->first();

            if (!$verificaExistenciaTemplate) {
                // Preparando os dados para salvar no formato JSONB (template de e-mail)
                $templateData = [
                    'cod_encadeado' => $email->cod_encadeado,
                    'data_email' => $email->data_email,
                    'respondido' => $email->respondido,
                    'status' => $email->status,
                    'resposta' => $email->resposta,
                    'data_resposta' => $email->data_resposta,
                    'resolvido' => $email->resolvido,
                    'controle_interno' => $email->controle_interno,
                    'atualizado' => $email->atualizado,
                    'quem_confirmo_exclusao' => $email->quem_confirmo_exclusao,
                    'quem_respondeu' => $email->quem_respondeu,
                    'id_quem_respondeu' => $email->id_quem_respondeu,
                    'copia' => $email->copia,
                    'exige_resposta' => $email->exige_resposta,
                    'id_requisitado' => $email->id_requisitado,
                    'modulo' => $email->modulo,
                    'problema' => $email->problema,
                    'importante' => $email->importante,
                    'id_controle' => $email->id_controle,
                    'id_categoria' => $email->id_categoria,
                    'data_insert' => $email->data_insert,  // Pode ser a data atual ou a data da inserção
                ];

                // Inserindo os dados na tabela email_complements_template com o campo JSONB
                DB::connection('pgsql')->table('email_complements_template')->insert([
                    'client_id' => $clientId,  // Relacionamento com a tabela clients
                    'template_data' => json_encode($templateData),  // Salvando o template como JSONB
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }



            /*            if ($verificaExistencia) {
                DB::connection('pgsql')
                    ->table('email_complements')
                    ->where('id', '=', $email->id)
                    ->update([
                        'token' => $token,
                    ]);
            }
*/
        }

        // DB::connection('mysqlSE')->table('new_email')->select('id')->where('id', '=', 1)->first();



        // DB::connection('mysql')
        //     ->table('email')
        //     ->whereNotNull('to_email')
        //     ->where('to_email', '!=', '')
        //     ->whereNotNull('from_email')
        //     ->where('from_email', '!=', '')
        //     ->orderBy('id')
        //     ->chunk(1000, function ($emails) {
        //         $entities = [];
        //         foreach ($emails as $email) {
        //             $id = $email->id;
        //             $from = $email->from_email;
        //             $to = [$email->to_email];
        //             $cc = [$email->cc_email];
        //             $bcc = [];
        //             $attachments = [];
        //             $subject = $email->assunto;
        //             $body = $email->texto;
        //             $processedAt = $email->data_insert;
        //             $status = $email->status;

        //             if ($status == 0) {
        //                 // nao lido
        //                 $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
        //                 $emailFolderEnum = EmailFolderEnum::INBOX;
        //                 $isDeleted = false;
        //                 $readAt = null;
        //                 $isRead = false;
        //             } else if ($status == 1) {
        //                 // lido
        //                 $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
        //                 $emailFolderEnum = EmailFolderEnum::INBOX;
        //                 $isDeleted = false;
        //                 $readAt = null;
        //                 $isRead = true;
        //             } else if ($status == 2) {
        //                 // lixo
        //                 $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
        //                 $emailFolderEnum = EmailFolderEnum::TRASH;
        //                 $isDeleted = false;
        //                 $readAt = null;
        //                 $isRead = true;
        //             } else if ($status == 3) {
        //                 // excluido
        //                 $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
        //                 $emailFolderEnum = EmailFolderEnum::TRASH;
        //                 $isDeleted = true;
        //                 $readAt = null;
        //                 $isRead = true;
        //             } else if ($status == 4) {
        //                 //email enviado (@superestagios.com.br)
        //                 $emailDirectionEnum = EmailDirectionEnum::SENT;
        //                 $emailFolderEnum = EmailFolderEnum::SENT;
        //                 $isDeleted = false;
        //                 $readAt = null;
        //                 $isRead = false;
        //             } else {

        //                 if (str_contains($to[0], "@superestagios.com.br")) {
        //                     $emailDirectionEnum = EmailDirectionEnum::SENT;
        //                     $emailFolderEnum = EmailFolderEnum::SENT;
        //                     $isDeleted = false;
        //                     $readAt = null;
        //                     $isRead = false;
        //                 } else {
        //                     $emailDirectionEnum = EmailDirectionEnum::RECEIVED;
        //                     $emailFolderEnum = EmailFolderEnum::INBOX;
        //                     $isDeleted = false;
        //                     $readAt = null;
        //                     $isRead = false;
        //                 }
        //             }


        //             $entities[] = [
        //                 'id' => $id,
        //                 'from' => $from,
        //                 'to' => $to,
        //                 'cc' => $cc,
        //                 'bcc' => $bcc,
        //                 'attachments' => $attachments,
        //                 'subject' => $subject,
        //                 'body' => $body,
        //                 'threadId' => null,
        //                 'processedAt' => $processedAt,
        //                 'received' => $emailDirectionEnum,
        //                 'folder' => $emailFolderEnum,
        //                 'isDeleted' => $isDeleted,
        //                 'readAt' => $readAt,
        //                 'isRead' => $isRead,
        //             ];
        //         }
        //         $this->info("Inserindo " . count($entities) . " registros...");
        //         DB::connection('mongodb')->table('emails')->insert($entities);
        //     });

        // $this->info('Migração concluída com sucesso!');
        $this->info("Aqui foi" . json_encode($email_arquivo_morto));
    }
}


function uuidv7_from_timestamp(int $timestampMillis): string
{
    if ($timestampMillis < 0 || $timestampMillis > 0xFFFFFFFFFFFF) {
        throw new InvalidArgumentException("Timestamp inválido para UUIDv7.");
    }

    // Parte 1: timestamp de 48 bits (12 hex chars)
    $timeHex = str_pad(dechex($timestampMillis), 12, '0', STR_PAD_LEFT);

    // Parte 2: versão 7 nos 4 bits mais significativos do 3º grupo
    $time_hi = substr($timeHex, 8, 4);
    $time_hi_int = hexdec($time_hi);
    $time_hi_version = dechex(($time_hi_int & 0x0FFF) | 0x7000); // set versão 7

    // Parte 3: variant RFC4122 (10xx xxxx)
    $clock_seq_hi = dechex((random_int(0, 0x3F) | 0x80));
    $clock_seq_low = dechex(random_int(0, 0xFF));

    // Parte 4: entropia (64 bits restantes)
    $node = bin2hex(random_bytes(6));

    return strtolower(sprintf(
        '%s-%s-%s-%s%s-%s',
        substr($timeHex, 0, 8),             // time_low
        substr($timeHex, 8, 4),             // time_mid
        $time_hi_version,                   // time_hi_and_version
        $clock_seq_hi,                      // clock_seq_hi_and_reserved
        str_pad($clock_seq_low, 2, '0', STR_PAD_LEFT), // clock_seq_low
        $node                               // node
    ));
}

