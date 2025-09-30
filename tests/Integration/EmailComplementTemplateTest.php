<?php

use App\Data\Input\SaveEmailComplementInputData;
use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\UseCases\SaveEmailComplement;
use App\Util\UUID;
use Tests\TestCase;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementTemplateRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use Illuminate\Support\Facades\DB;

uses(TestCase::class);

class EmailComplementTemplateTestContainer
{
    public readonly EmailComplementTemplateRepository $emailComplementTemplateRepository;
    public readonly ClientRepository $clientRepository;

    public function __construct()
    {
        $this->emailComplementTemplateRepository = new FacadesEmailComplementTemplateRepository();
        $this->clientRepository = new FacadesClientRepository();
    }
}

describe('Database: Register Template', function () {
    beforeEach(function () {
        DB::table('email_complements_template')->delete();
        DB::table('clients')->delete();

        $container = new EmailComplementTemplateTestContainer();
        $this->clientRepository = $container->clientRepository;
        $this->emailComplementTemplateRepository = $container->emailComplementTemplateRepository;

        $this->saveEmailComplement = new SaveEmailComplement(
            emailComplementTemplateRepository: $this->emailComplementTemplateRepository
        );

        $client = Client::create(
            name: 'Test Client',
            token: UUID::v4(),
            domain: 'gruposuper.com.br',
            id: UUID::v7(),
        );

        $this->clientRepository->save($client);
        $this->client = $client;
    });

    it('should save allowed types: string and int', function () {
        $inputData = [
            'template' => (object)[
                'content' => ['string', 'int']
            ],
            'client' => $this->client
        ];

        $input = SaveEmailComplementInputData::validateAndCreate($inputData);
        $response = $this->saveEmailComplement->execute($input);

        $template = DB::table('email_complements_template')
            ->where('id', $response->email_complement_template->getId())
            ->first();

        expect(json_decode($template->template)->content)->toBe(['string', 'int']);
    });

    it('should throw error if content is not an array', function () {
        $this->expectException(\InvalidArgumentException::class);

        $inputData = [
            'template' => (object)[
                'content' => 'not-an-array'
            ],
            'client' => $this->client
        ];

        $input = SaveEmailComplementInputData::validateAndCreate($inputData);
        $this->saveEmailComplement->execute($input);
    });

    it('should throw error for unsupported type', function () {
        $this->expectException(\InvalidArgumentException::class);

        $inputData = [
            'template' => (object)[
                'content' => ['string', 'banana']
            ],
            'client' => $this->client
        ];

        $input = SaveEmailComplementInputData::validateAndCreate($inputData);
        $this->saveEmailComplement->execute($input);
    });

    it('should throw error if array contains non-string values', function () {
        $this->expectException(\InvalidArgumentException::class);

        $inputData = [
            'template' => (object)[
                'content' => ['string', true, 123]
            ],
            'client' => $this->client
        ];

        $input = SaveEmailComplementInputData::validateAndCreate($inputData);
        $this->saveEmailComplement->execute($input);
    });

    it('should accept all valid types', function () {
        $inputData = [
            'template' => (object)[
                'content' => ['string', 'int', 'boolean', 'array']
            ],
            'client' => $this->client
        ];

        $input = SaveEmailComplementInputData::validateAndCreate($inputData);
        $response = $this->saveEmailComplement->execute($input);

        $template = DB::table('email_complements_template')
            ->where('id', $response->email_complement_template->getId())
            ->first();

        expect(json_decode($template->template)->content)->toBe(['string', 'int', 'boolean', 'array']);
    });
});
