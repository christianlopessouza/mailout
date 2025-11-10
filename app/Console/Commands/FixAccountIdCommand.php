<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAccountIdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-account-id {--limit=1000 : Número máximo de registros a processar} {--dry-run : Executa sem fazer alterações}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige o account_id dos emails no GSmail baseado nos dados do Smail';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        DB::disableQueryLog();

        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('=== MODO DRY-RUN ATIVADO - Nenhuma alteração será feita ===');
        }

        $this->info('Iniciando processo de correção de account_id...');
        $this->newLine();

        // Busca emails no GSmail que não são de @superestagios.com.br
        $emails = DB::connection('pgsql')
            ->select("
                SELECT DISTINCT
                    a.email_address,
                    e.id as email_id,
                    e.external_id,
                    e.account_id as current_account_id,
                    e.processed_at
                FROM
                    emails AS e
                    INNER JOIN email_search_tokens AS est ON e.id = est.email_id
                    LEFT JOIN email_complements AS ec ON e.id = ec.email_id
                    INNER JOIN accounts AS a ON e.account_id = a.id
                WHERE
                    a.email_address::TEXT NOT ILIKE ?
                    AND e.external_id IS NOT NULL
                ORDER BY e.processed_at DESC
                LIMIT ?
            ", ['%@superestagios.com.br', $limit]);

        $total = count($emails);
        $this->info("Total de emails encontrados: {$total}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $stats = [
            'processados' => 0,
            'atualizados' => 0,
            'sem_external_id' => 0,
            'sem_email_smail' => 0,
            'sem_account_gsmail' => 0,
            'erros' => 0,
            'ja_correto' => 0,
            'origem_email' => 0,
            'origem_email_arquivo' => 0,
            'origem_email_enviado_super' => 0
        ];

        foreach ($emails as $email) {
            $stats['processados']++;

            try {
                // Verifica se tem external_id
                if (!$email->external_id) {
                    $stats['sem_external_id']++;
                    $progressBar->advance();
                    continue;
                }

                // 1. Busca o email_address no Smail usando o external_id (nas 3 tabelas: email, email_arquivo, email_enviado_super)
                $smailEmailConta = DB::connection('mysqlSMAIL')
                    ->select("
                        SELECT ec.email, e.id_conta, 'email' as origem
                        FROM email e
                        INNER JOIN email_cont ec ON e.id_conta = ec.id
                        WHERE e.id = ?
                        
                        UNION ALL
                        
                        SELECT ec.email, ea.id_conta, 'email_arquivo' as origem
                        FROM email_arquivo ea
                        INNER JOIN email_cont ec ON ea.id_conta = ec.id
                        WHERE ea.id = ?
                        
                        UNION ALL
                        
                        SELECT ec.email, ees.id_conta, 'email_enviado_super' as origem
                        FROM email_enviado_super ees
                        INNER JOIN email_cont ec ON ees.id_conta = ec.id
                        WHERE ees.id = ?
                        
                        LIMIT 1
                    ", [$email->external_id, $email->external_id, $email->external_id]);
                
                $smailEmailConta = !empty($smailEmailConta) ? $smailEmailConta[0] : null;

                if (!$smailEmailConta || !$smailEmailConta->email) {
                    $stats['sem_email_smail']++;
                    $this->newLine();
                    $this->warn("Email {$email->email_id}: Email ou conta não encontrados no Smail (external_id: {$email->external_id})");
                    $progressBar->advance();
                    continue;
                }

                // Registra de qual tabela veio
                if ($smailEmailConta->origem === 'email') {
                    $stats['origem_email']++;
                } elseif ($smailEmailConta->origem === 'email_arquivo') {
                    $stats['origem_email_arquivo']++;
                } elseif ($smailEmailConta->origem === 'email_enviado_super') {
                    $stats['origem_email_enviado_super']++;
                }

                $emailAddressSmail = trim($smailEmailConta->email);

                // 3. Busca o id da account no GSmail usando o email_address do Smail
                $gsmailAccount = DB::connection('pgsql')
                    ->table('accounts')
                    ->select('id')
                    ->where('email_address', $emailAddressSmail)
                    ->first();

                if (!$gsmailAccount) {
                    $stats['sem_account_gsmail']++;
                    $this->newLine();
                    $this->warn("Email {$email->email_id}: Account não encontrada no GSmail (email: {$emailAddressSmail})");
                    $progressBar->advance();
                    continue;
                }

                $correctAccountId = $gsmailAccount->id;

                // Verifica se já está correto
                if ($email->current_account_id === $correctAccountId) {
                    $stats['ja_correto']++;
                    $progressBar->advance();
                    continue;
                }

                // 4. Atualiza o account_id na tabela emails do GSmail
                if (!$dryRun) {
                    DB::connection('pgsql')
                        ->table('emails')
                        ->where('id', $email->email_id)
                        ->update(['account_id' => $correctAccountId]);
                }

                $stats['atualizados']++;
                
                $this->newLine();
                $this->info("✓ Email {$email->email_id}: account_id atualizado");
                $this->line("  - De: {$email->email_address} ({$email->current_account_id})");
                $this->line("  - Para: {$emailAddressSmail} ({$correctAccountId})");
                $this->line("  - Origem Smail: {$smailEmailConta->origem}");

            } catch (\Exception $e) {
                $stats['erros']++;
                $this->newLine();
                $this->error("Email {$email->email_id}: Erro - " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Exibe estatísticas
        $this->info('=== ESTATÍSTICAS ===');
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total Processados', $stats['processados']],
                ['Atualizados' . ($dryRun ? ' (simulado)' : ''), $stats['atualizados']],
                ['Já Corretos', $stats['ja_correto']],
                ['Sem External ID', $stats['sem_external_id']],
                ['Sem Email/Conta no Smail', $stats['sem_email_smail']],
                ['Sem Account no GSmail', $stats['sem_account_gsmail']],
                ['Erros', $stats['erros']],
            ]
        );

        $this->newLine();
        $this->info('=== ORIGEM DOS DADOS NO SMAIL ===');
        $this->table(
            ['Tabela Smail', 'Quantidade'],
            [
                ['email', $stats['origem_email']],
                ['email_arquivo', $stats['origem_email_arquivo']],
                ['email_enviado_super', $stats['origem_email_enviado_super']],
            ]
        );

        $this->newLine();
        if ($dryRun) {
            $this->warn('Este foi um DRY-RUN. Execute sem --dry-run para aplicar as alterações.');
        } else {
            $this->info('Processo finalizado com sucesso!');
        }

        return Command::SUCCESS;
    }
}

