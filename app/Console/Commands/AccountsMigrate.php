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

class AccountsMigrate extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-accounts';

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

        $accountRepository = new FacadesAccountRepository();

        $logins = file_get_contents(__DIR__.'/logins_senhas.json');
        $logins = json_decode($logins, true);

        foreach ($logins as $key => $value) {
            $account = Account::create(
                email_address: $value['login']."@superestagios.com.br",
                password: Crypto::encrypt($value['senha']),
                host: 'aws-cpanel',
                port: 587,
                type: AccountType::SENDER
            );
            $accountRepository->save($account);
            $this->info("Conta {$value['login']} migrada com sucesso!");
        }


        //$email_enviado_super = DB::connection('mysqlSMAIL')->select(
        //     "SELECT e.*,ec.email as email_conta
        //     FROM email_enviado_super e
        //     LEFT JOIN email_cont ec ON ec.id = e.id_conta
        //     ORDER BY e.id ASC
        //     LIMIT 5
        //     OFFSET 0"
        // );


    }
}