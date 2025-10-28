<?php

namespace App\Console\Commands;

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
                ->select(['id', 'external_id', 'from', 'to', 'subject'])
                ->where(function ($query) use ($emailAddress) {
                    $query->where('from', 'like', "%{$emailAddress}%")
                        ->orWhere('to', 'like', "%{$emailAddress}%");
                })
                ->whereNotNull('external_id')
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

            foreach ($gSmailEmails as $gSmailEmail) {
                $this->line("📧 Processando GSmail ID: {$gSmailEmail->id} (External ID: {$gSmailEmail->external_id})");

                // Buscar dados no Smail
                $smailEmail = DB::connection('mysqlSMAIL')
                    ->table('email')
                    ->where('id', $gSmailEmail->external_id)
                    ->first();

                if (!$smailEmail) {
                    $this->warn("   ⚠️  Email ID {$gSmailEmail->external_id} não encontrado no Smail");
                    $errors++;
                    continue;
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
                'host' => 'supermail-postgresql.cf8mcewa40ak.us-east-1.rds.amazonaws.com',
                'port' => '5432',
                'database' => 'postgres',
                'username' => 'supermail_admin',
                'password' => 'W5%iXx4=W4aoRyd_#WSaz)0Azz{aS%y&',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]
        ]);

        // Configurar MySQL (Smail)
        config([
            'database.connections.mysqlSMAIL' => [
                'driver' => 'mysql',
                'host' => '186.237.198.38',
                'port' => '3306',
                'database' => 'smail_oficial',
                'username' => 'smail_user',
                'password' => 'qIZ,_vfFC{Zp*jhV(h',
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
