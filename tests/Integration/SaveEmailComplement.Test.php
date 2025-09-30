<?php

use App\Data\Input\SaveEmailComplementInputData;
use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementTemplateRepository;
use App\UseCases\SaveEmailComplement;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

class SaveEmailComplementContainer
{
    public readonly EmailComplementTemplateRepository $emailComplementTemplateRepository;
    public readonly ClientRepository $clientRepository;

    public function __construct()
    {
        $this->emailComplementTemplateRepository = new FacadesEmailComplementTemplateRepository();
        $this->clientRepository = new FacadesClientRepository();
    }
}

describe('Save Email Complement Template', function () {
    beforeEach(function () {
        DB::table('email_complements_template')->delete();
        DB::table('clients')->delete();
        
        $container = new SaveEmailComplementContainer();

        $client = Client::create('Test Client', UUID::v4(), 'example.com');
        $container->clientRepository->save($client);

        $this->saveEmailComplement = new SaveEmailComplement(
            emailComplementTemplateRepository: $container->emailComplementTemplateRepository
        );

        $this->client = $client;
        $this->emailComplementTemplateRepository = $container->emailComplementTemplateRepository;

        $this->input = [
            'template' => (object)[
                'content' => 'string',
                'subject_id' => 'int'
            ],
            'client' => $this->client
        ];
    });

    it("should save template", function () {
        $input = SaveEmailComplementInputData::validateAndCreate($this->input);
        $response = $this->saveEmailComplement->execute($input);

        $email_complement_template = $response->email_complement_template;

        expect($email_complement_template->getId())->not()->toBeEmpty();
    });

    it("shouldn't save template with invalid type", function () {
        $this->input['template']->new_element = 'float';

        $input = SaveEmailComplementInputData::validateAndCreate($this->input);

        $this->expectException(\InvalidArgumentException::class);

        $this->saveEmailComplement->execute($input);
    });
});
