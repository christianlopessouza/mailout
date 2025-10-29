<?php

namespace App\Console\Commands;

use App\Data\EmailTokens;
use App\Domain\Entities\Account;
use App\Domain\Entities\Email;
use App\Domain\Enums\AccountType;
use App\Domain\Enums\Direction;
use App\Domain\Enums\Origin;
use App\Helper\Crypto;
use App\Infrastructure\Persistence\EmailComplementDTO;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Util\UUID;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DBMigrateCommand3 extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-mysql-to-postgree3';

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
        ini_set('memory_limit', '-1'); // ou '2G'
        DB::disableQueryLog();        // importantíssim

        $accounts_cache = [];
        $accountRepository = new FacadesAccountRepository();
        $emailRepository = new FacadesEmailRepository();
        $emailComplementRepository = new FacadesEmailComplementRepository();


        
        $email_enviado_super = DB::connection('mysqlSMAIL')->select(
            "SELECT e.*,ec.email as email_conta
            FROM email e
            LEFT JOIN email_cont ec ON ec.id = e.id_conta
            WHERE ec.email IN ('operacional.vitoria@superestagios.com.br','operacional@superestagios.com.br','ariene.thomaz@superestagios.com.br','jr.fagundes@superestagios.com.br','adm.vale@superestagios.com.br','superatendimento@superestagios.com.br','daniela.s@superestagios.com.br','unidade.cuiaba@superestagios.com.br','comercial.cuiaba@superestagios.com.br','celso.andrade@superestagios.com.br','operacional.caixa@superestagios.com.br','comercial@superestagios.com.br','julianatorres@superestagios.com.br','samf@superestagios.com.br','convencao@superestagios.com.br','atendimento.vix@superestagios.com.br','rh.caixa@superestagios.com.br','poliana@superestagios.com.br','poliana.modenesi@superestagios.com.br')
            AND MONTH(e.data_email) = 10 AND YEAR(e.data_email) = 2025
            ORDER BY e.id DESC
            LIMIT 100000000
            "
        );




        foreach ($email_enviado_super as $key => $email) {

            $complements = [
                'id_categoria' => $email->id_categoria ?? null,
                'problema' => $email->problema ?? null,
                'modulo' => $email->modulo ?? null,
                'importante' => $email->importante ?? null,
                'id_requisitado' => $email->id_requisitado ?? null,
                'copia' => $email->copia ?? null,
                'exige_resposta' => $email->exige_resposta ?? null,
                'id_quem_respondeu' => $email->id_quem_respondeu ?? null,
                'quem_respondeu' => $email->quem_respondeu ?? null,
                'atualizado' => $email->atualizado ?? null,
                'controle_interno' => $email->controle_interno ?? null,
                'resolvido' => $email->resolvido ?? null,
                'data_resposta' => $email->data_resposta ?? null,
                'resposta' => $email->resposta ?? null,
                'respondido' => $email->respondido ?? null,
                'status' => $email->status ?? null,
                'quem_confirmo_exclusao' => $email->quem_confirmo_exclusao ?? null,
                'cod_encadeado' => $email->cod_encadeado ?? null,
                'id_controle' => $email->id_controle ?? null,
                'codigo_email' => $email->codigo_email ?? null
            ];

            $email_from = $email->from_email;

            $existeGSmail = DB::connection('pgsql')
                ->table('emails')
                ->where('external_id', '=', $email->id)
                ->first();

            if (!$existeGSmail) {
                if (strpos($email_from, 'superestagios.com.br') !== false) {
                    $direction = Direction::OUTGOING;
                } else {
                    $direction = Direction::INCOMING;
                }

                if (!$email_from) {
                    if (strpos($email_from, 'superestagios.com.br') !== false) {
                        $email->email_conta = $email_from;
                    } else if (trim($email->email_conta) === '') {
                        $email->email_conta = 'no-account@superestagios.com.br';
                    } else {
                        $this->error('Email sem remetente (provavel externo): ' . $email->id);
                        continue;
                    }
                }

                // verifica se a conta existe
                if (array_key_exists($email_from, $accounts_cache)) {
                    $id_conta = $accounts_cache[$email_from];
                } else {
                    $verificaEmailConta = DB::connection('pgsql')
                        ->table('accounts')
                        ->select('id')
                        ->where('email_address', '=', $email_from)
                        ->first();

                    if (!$verificaEmailConta) {
                        $data_email = (int) new DateTime($email->data_email)->format('Uv');
                        $account = Account::create(
                            email_address: $email_from,
                            password: Crypto::encrypt('BCNsddU3IeXeOiXGcUk3ie0d7YYabzi9+gzgJ2Vdix6T'),
                            host: 'email-smtp.us-east-1.amazonaws.com',
                            port: 587,
                            username: 'AKIAVAVZ53YBCDCS4VVV',
                            type: AccountType::SENDER,
                        );
                        $accountRepository->save($account);
                        $id_conta = $account->getId();
                    } else {
                        $id_conta = $verificaEmailConta->id;
                    }

                    $accounts_cache[$email_from] = $id_conta;
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
                } else if ($email->status == 5 || $email->modulo || $email->id_requisitado) {
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
                ############

                $email->assunto = trim($email->assunto) === '' ? '(sem assunto)' : trim($email->assunto);
                $email->texto = trim($email->texto) === '' ? '(sem corpo)' : trim($email->texto);

                $resolved_complements = null;
               
                $emailEntity = Email::create(
                    id: $this->uuidv7_from_timestamp($data_email),
                    account_id: $id_conta,
                    from: $email_from,
                    to: $email_to,
                    cc: $email_cc,
                    bcc: null,
                    subject: $email->assunto,
                    body: $email->texto,
                    direction: $direction,
                    origin: $origin,
                    folder_id: $folder,
                    read: $lido,
                    read_at: $data_lido,
                    deleted: $deleted,
                    processed_at: new DateTime($email->data_email),
                    external_id: $email->id,
                    attachments: false
                );

                $complement_data = (object)[
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
                    'codigo_email' => null
                ];
                $resolved_complements = $this->resolver_template((object)$complements, $complement_data);
                $emailRepository->save($emailEntity);
                if ($resolved_complements) {
                    $this->info('Migrando complementos do email');
                    $email_complements = EmailComplementDTO::validateAndCreate([
                        'email_id' => $emailEntity->getId(),
                        'complements' => $resolved_complements
                    ]);
                    $emailComplementRepository->save($email_complements);
                }

                $this->info("\033[32m" . $key . '- Email migrado ' . $email->id . ' com sucesso: ' . $emailEntity->getId() . "\033[0m");
            } else {
                $this->error($key . '- Já existe o email com ID: ' . $email->id . ', pulando...');
            }
        }
    }

    private function uuidv7_from_timestamp(int $timestampMillis): string
    {
        if ($timestampMillis < 0 || $timestampMillis > 0xFFFFFFFFFFFF) {
            throw new InvalidArgumentException(message: 'Timestamp inválido para UUIDv7.');
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

    private function resolver_template(object $templater, object $complements_values): object
    {
        $template = clone $templater;
        foreach ($template as $key => $_) {
            $template->{$key} = $complements_values->{$key};
        }
        return $template;
    }
}