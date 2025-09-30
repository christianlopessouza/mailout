<?php

namespace App\Http\Master\Controllers;

use App\Data\Input\SaveEmailComplementInputData;
use App\UseCases\SaveEmailComplement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveEmailComplementController
{
    public function __construct(
        private SaveEmailComplement $saveEmailComplement
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var \App\Domain\Entities\Client $client */
            $client = $request->attributes->get('client');

            $input = SaveEmailComplementInputData::validateAndCreate([
                'client' => $client->getId(),
                'template' => $request->input('template'),
            ]);

            $this->saveEmailComplement->execute($input);

            return response()->json([
                'message' => 'Email complement saved successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error saving email complement',
                'error'   => $th->getMessage(),
            ], 400);
        }
    }
}
