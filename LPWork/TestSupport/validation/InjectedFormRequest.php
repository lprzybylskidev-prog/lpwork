<?php

declare(strict_types=1);

namespace Tests\support\validation;

use Tests\support\routing\InjectedMessage;

final class InjectedFormRequest extends StorePostFormRequest
{
    public function __construct(
        private readonly InjectedMessage $message,
    ) {}

    public function message(): InjectedMessage
    {
        return $this->message;
    }
}
