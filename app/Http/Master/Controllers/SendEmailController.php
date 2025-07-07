<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\SendEmailInputData;
use App\UseCases\SendEmail;
use App\UseCases\SaveEmailComplement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendEmailController
{
    public function __construct(
        private SendEmail $sendEmail,
        private SaveEmailComplement $saveEmailComplement
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $account = $request->attributes->get('account');

            // Recebe os dados do e-mail
            $input = SendEmailInputData::validateAndCreate([
                'account' => $account,
                'email_data' => [
                    'to' => $request->input('to'),
                    'cc' => $request->input('cc'),
                    'bcc' => $request->input('bcc'),
                    'subject' => $request->input('subject'),
                    'body' => $request->input('body'),
                    'attachments' => $request->input('attachments'),
                    'origin' => $request->input('origin'),
                    'complements' => $request->input('complement'),
                    'reply_to' => $request->input('reply_to'),
                    'thread_id' => $request->input('thread_id'),
                ]
            ]);

            // Envia o e-mail (metodologia já existente)
            $sendEmailOutput = $this->sendEmail->execute($input);

            // Obtém o ID do e-mail diretamente do Email (não do SendEmailOutputData)
            $emailId = $sendEmailOutput->email->getId();  // Aqui obtemos o ID do Email diretamente

            // // Dados do complemento de e-mail
            // $emailData = [
            //     'cod_encadeado' => $request->input('cod_encadeado'),
            //     'data_email' => $request->input('data_email'),
            //     'respondido' => $request->input('respondido'),
            //     'status' => $request->input('status'),
            //     'resposta' => $request->input('resposta'),
            //     'data_resposta' => $request->input('data_resposta'),
            //     'resolvido' => $request->input('resolvido'),
            //     'controle_interno' => $request->input('controle_interno'),
            //     'atualizado' => $request->input('atualizado'),
            //     'quem_confirmo_exclusao' => $request->input('quem_confirmo_exclusao'),
            //     'quem_respondeu' => $request->input('quem_respondeu'),
            //     'id_quem_respondeu' => $request->input('id_quem_respondeu'),
            //     'copia' => $request->input('copia'),
            //     'exige_resposta' => $request->input('exige_resposta'),
            //     'id_requisitado' => $request->input('id_requisitado'),
            //     'modulo' => $request->input('modulo'),
            //     'problema' => $request->input('problema'),
            //     'importante' => $request->input('importante'),
            //     'id_controle' => $request->input('id_controle'),
            //     'id_categoria' => $request->input('id_categoria'),
            //     'data_insert' => now(),
            // ];

            // // Salvar o complemento de e-mail
            // $this->saveEmailComplement->executeEmailComplement($emailData, $emailId);

            // // Dados para o template de e-mail (relacionado ao cliente)
            // $templateData = [
            //     'cod_encadeado' => $request->input('cod_encadeado'),
            //     'data_email' => $request->input('data_email'),
            //     'respondido' => $request->input('respondido'),
            //     'status' => $request->input('status'),
            //     'resposta' => $request->input('resposta'),
            //     'data_resposta' => $request->input('data_resposta'),
            //     'resolvido' => $request->input('resolvido'),
            //     'controle_interno' => $request->input('controle_interno'),
            //     'atualizado' => $request->input('atualizado'),
            //     'quem_confirmo_exclusao' => $request->input('quem_confirmo_exclusao'),
            //     'quem_respondeu' => $request->input('quem_respondeu'),
            //     'id_quem_respondeu' => $request->input('id_quem_respondeu'),
            //     'copia' => $request->input('copia'),
            //     'exige_resposta' => $request->input('exige_resposta'),
            //     'id_requisitado' => $request->input('id_requisitado'),
            //     'modulo' => $request->input('modulo'),
            //     'problema' => $request->input('problema'),
            //     'importante' => $request->input('importante'),
            //     'id_controle' => $request->input('id_controle'),
            //     'id_categoria' => $request->input('id_categoria'),
            //     'data_insert' => now(),
            // ];

            // $clientId = $account->getId();  // Obtém o ID do cliente
            // $this->saveEmailComplement->executeEmailTemplate($templateData, $clientId);

            return response()->json([
                'message' => 'Mail sent successfully, complement and template saved',
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
