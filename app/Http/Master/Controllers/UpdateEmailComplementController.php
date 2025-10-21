<?php

namespace App\Http\Master\Controllers;

use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateEmailComplementController
{
    public function __construct(
        private EmailRepository $emailRepository,
        private EmailComplementRepository $emailComplementRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $complements = $request->input('complements');
            $id = $request->id;

            $email = $this->emailRepository->findById($id);
            if (!$email) {
                throw new \Exception('Email not found');
            }

            $email_complement = $this->emailComplementRepository->findByEmailId($email->getId());
            if (!$email_complement) {
                throw new \Exception('Complement not found');
            }

            $complement_original = $email_complement->complements;
 
            foreach ($complements as $key => $value) {
                $complement_original->{$key} = $value;
            }

            $this->emailComplementRepository->update(
                email_id: $email->getId(),
                complements: $email_complement->complements
            );

            return response()->json([
                'message' => 'Email complement updated successfully',
            ], 200);
        } catch (\Exception $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }
}
