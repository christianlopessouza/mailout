<?php

namespace App\Console\Commands;

use App\Domain\Enums\Direction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class SyncSpecificEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-specific-email {email : Endereço de email para sincronizar} {--dry-run : Modo de teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza emails de um endereço específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emailAddress = $this->argument('email');
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Sincronizando emails de: {$emailAddress}");

        if ($dryRun) {
            $this->warn("🧪 MODO DRY RUN - Nenhuma alteração será feita!");
        }

        // Configurar conexões
        $this->setupConnections();

        try {
            // 1. Buscar emails no GSmail com o endereço específico
            $this->info("📥 Buscando emails no GSmail...");
            $gSmailEmails = DB::connection('pgsql')
                ->table('emails')
                ->join('email_search_tokens', 'emails.id', '=', 'email_search_tokens.email_id')
                ->join('accounts', 'emails.account_id', '=', 'accounts.id')
                ->select(['emails.id', 'emails.external_id', 'emails.from', 'emails.to', 'emails.subject', 'emails.processed_at', 'accounts.email_address', 'e.direction'])
                ->whereIn('email_search_tokens.value', ['operacional.vitoria@superestagios.com.br', 'operacional@superestagios.com.br', 'ariene.thomaz@superestagios.com.br', 'jr.fagundes@superestagios.com.br', 'adm.vale@superestagios.com.br', 'superatendimento@superestagios.com.br', 'daniela.s@superestagios.com.br', 'unidade.cuiaba@superestagios.com.br', 'comercial.cuiaba@superestagios.com.br', 'celso.andrade@superestagios.com.br', 'operacional.caixa@superestagios.com.br', 'comercial@superestagios.com.br', 'julianatorres@superestagios.com.br', 'samf@superestagios.com.br', 'convencao@superestagios.com.br', 'atendimento.vix@superestagios.com.br', 'rh.caixa@superestagios.com.br', 'poliana@superestagios.com.br', 'poliana.modenesi@superestagios.com.br'])
                ->whereIn('email_search_tokens.type', ['from', 'to', 'cc', 'bcc'])
                ->whereNotNull('emails.external_id')
                ->whereYear('emails.processed_at', 2025)
                ->distinct()
                ->orderBy('emails.processed_at', 'desc')
                ->get();

            $this->info("✅ {$gSmailEmails->count()} emails encontrados no GSmail");

            if ($gSmailEmails->isEmpty()) {
                $this->warn("⚠️  Nenhum email encontrado com o endereço: {$emailAddress}");
                return 0;
            }

            // 2. Mostrar emails encontrados
            $this->info("📋 Emails encontrados:");
            foreach ($gSmailEmails as $email) {
                $this->line("   • ID: {$email->id}, External ID: {$email->external_id}");
                $this->line("     Data: {$email->processed_at}");
                $this->line("     From: {$email->from}");
                $this->line("     To: " . (is_string($email->to) ? $email->to : json_encode($email->to)));
                $this->line("     Subject: " . substr($email->subject ?? '', 0, 50) . "...");
                $this->line("");
            }

            // 3. Processar sincronização
            $processed = 0;
            $created = 0;
            $updated = 0;
            $errors = 0;
            $fromEmail = 0;
            $fromEmailArquivo = 0;

            foreach ($gSmailEmails as $gSmailEmail) {
                $this->line("📧 Processando GSmail ID: {$gSmailEmail->id} (External ID: {$gSmailEmail->external_id})");

                // Buscar dados no Smail (nas tabelas email e email_arquivo)
                $smailEmailResult = DB::connection('mysqlSMAIL')
                    ->select("
                        SELECT *, 'email' as origem_tabela
                        FROM email
                        WHERE id = ?
                        
                        UNION ALL
                        
                        SELECT *, 'email_arquivo' as origem_tabela
                        FROM email_arquivo
                        WHERE id = ?
                        
                        LIMIT 1
                    ", [$gSmailEmail->external_id, $gSmailEmail->external_id]);

                $smailEmail = !empty($smailEmailResult) ? $smailEmailResult[0] : null;

                if (!$smailEmail) {
                    $this->warn("   ⚠️  Email ID {$gSmailEmail->external_id} não encontrado no Smail");
                    $errors++;
                    continue;
                }

                $this->line("   📁 Encontrado em: {$smailEmail->origem_tabela}");

                // Contabiliza a origem
                if ($smailEmail->origem_tabela === 'email') {
                    $fromEmail++;
                } elseif ($smailEmail->origem_tabela === 'email_arquivo') {
                    $fromEmailArquivo++;
                }

                // Preparar dados complementares
                $complementData = [
                    'status' => $smailEmail->status,
                    'resolvido' => $smailEmail->resolvido,
                    'respondido' => $smailEmail->respondido,
                    'resposta' => $smailEmail->resposta,
                    'data_resposta' => $smailEmail->data_resposta,
                    'controle_interno' => $smailEmail->controle_interno,
                    'atualizado' => $smailEmail->atualizado,
                    'quem_confirmo_exclusao' => $smailEmail->quem_confirmo_exclusao,
                    'quem_respondeu' => $smailEmail->quem_respondeu,
                    'id_quem_respondeu' => $smailEmail->id_quem_respondeu,
                    'copia' => $smailEmail->copia,
                    'exige_resposta' => $smailEmail->exige_resposta,
                    'id_requisitado' => $smailEmail->id_requisitado,
                    'modulo' => $smailEmail->modulo,
                    'problema' => $smailEmail->problema,
                    'importante' => $smailEmail->importante,
                    'id_controle' => $smailEmail->id_controle,
                    'id_categoria' => $smailEmail->id_categoria,
                    'data_insert' => $smailEmail->data_insert,
                    'cod_encadeado' => $smailEmail->cod_encadeado,
                    'data_email' => $smailEmail->data_email
                ];

                if ($dryRun) {
                    $this->line("   🧪 [DRY RUN] Dados: Status={$smailEmail->status}, Resolvido={$smailEmail->resolvido}");
                    $this->line("   🧪 [DRY RUN] Respondido={$smailEmail->respondido}, Resposta=" . substr($smailEmail->resposta ?? '', 0, 30) . "...");
                } else {
                    if ($smailEmail->email_from == $gSmailEmail->email_address) {
                        $direction = Direction::OUTGOING;
                    } else {
                        $direction = Direction::INCOMING;
                    }
                    if ($direction->value != $gSmailEmail->direction) {
                        DB::connection('pgsql')
                            ->table('emails')
                            ->where('id', $gSmailEmail->id)
                            ->update([
                                'direction' => $direction->value
                            ]);
                        $updated++;
                        $this->line("   ✅ Atualizado direção do email");
                    }
                    // Verificar se já existe complement
                    $existingComplement = DB::connection('pgsql')
                        ->table('email_complements')
                        ->where('email_id', $gSmailEmail->id)
                        ->first();

                    if ($existingComplement) {
                        // Atualizar existente
                        DB::connection('pgsql')
                            ->table('email_complements')
                            ->where('email_id', $gSmailEmail->id)
                            ->update([
                                'complement_data' => json_encode($complementData),
                                'updated_at' => now()
                            ]);
                        $updated++;
                        $this->line("   ✅ Atualizado complement existente");
                    } else {
                        // Criar novo
                        DB::connection('pgsql')
                            ->table('email_complements')
                            ->insert([
                                'email_id' => $gSmailEmail->id,
                                'complement_data' => json_encode($complementData),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        $created++;
                        $this->line("   ✅ Criado novo complement");
                    }
                }

                $processed++;
            }

            // 4. Resumo
            $this->info("🎉 Sincronização concluída!");
            $this->info("📊 Resumo:");
            $this->info("   • Processados: {$processed}");
            if (!$dryRun) {
                $this->info("   • Criados: {$created}");
                $this->info("   • Atualizados: {$updated}");
            }
            $this->info("   • Erros: {$errors}");
            $this->newLine();
            $this->info("📁 Origem dos dados no Smail:");
            $this->info("   • Tabela 'email': {$fromEmail}");
            $this->info("   • Tabela 'email_arquivo': {$fromEmailArquivo}");
        } catch (Exception $e) {
            $this->error("❌ Erro durante a sincronização: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function setupConnections()
    {
        // Configurar PostgreSQL (GSmail)
        config([
            'database.connections.pgsql' => [
                'driver' => 'pgsql',
                'host' => env('LEGACY_SYNC_PGSQL_HOST', '127.0.0.1'),
                'port' => env('LEGACY_SYNC_PGSQL_PORT', '5432'),
                'database' => env('LEGACY_SYNC_PGSQL_DATABASE', 'mailout'),
                'username' => env('LEGACY_SYNC_PGSQL_USERNAME', 'mailout'),
                'password' => env('LEGACY_SYNC_PGSQL_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => env('LEGACY_SYNC_PGSQL_SSLMODE', 'prefer'),
            ]
        ]);

        // Configurar MySQL (Smail)
        config([
            'database.connections.mysqlSMAIL' => [
                'driver' => 'mysql',
                'host' => env('LEGACY_SYNC_MYSQL_HOST', '127.0.0.1'),
                'port' => env('LEGACY_SYNC_MYSQL_PORT', '3306'),
                'database' => env('LEGACY_SYNC_MYSQL_DATABASE', 'mailout_legacy'),
                'username' => env('LEGACY_SYNC_MYSQL_USERNAME', 'mailout'),
                'password' => env('LEGACY_SYNC_MYSQL_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]
        ]);
    }
}
