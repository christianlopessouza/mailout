<?

namespace App\Data\Input;

use App\Domain\Entities\Email as EmailEntity;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;


class EmailSenderInputData extends Data
{
    public function __construct(
        public readonly Credentials $credentials,
        public readonly EmailEntity $email
    ){}
}

class Credentials
{
    public function __construct(
        #[Required, Email]
        public readonly string $email_address,
        #[Required]
        public readonly string $password
    ) {}
}
