<?php
declare(strict_types=1);

namespace LPwork\Translation\Contract;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contract for creating translators from configuration.
 */
interface TranslationProviderInterface
{
    /**
     * @return TranslatorInterface
     */
    public function createTranslator(): TranslatorInterface;
}
