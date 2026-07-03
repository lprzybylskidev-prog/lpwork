<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

/**
 * Represents the php source highlighter framework component.
 */
final readonly class PhpSourceHighlighter
{
    /**
     * Performs the highlight operation.
     */
    public function highlight(string $code): string
    {
        $tokens = token_get_all('<?php ' . $code);
        $html = '';
        $skipOpenTag = true;

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $html .= '<span class="lp-src-punctuation">' . $this->escape($token) . '</span>';

                continue;
            }

            [$type, $text] = $token;

            if ($skipOpenTag && $type === T_OPEN_TAG) {
                $skipOpenTag = false;

                continue;
            }

            $html .= '<span class="' . $this->classFor($type) . '">' . $this->escape($text) . '</span>';
        }

        return $html;
    }

    private function classFor(int $token): string
    {
        return match ($token) {
            T_ABSTRACT,
            T_AS,
            T_CATCH,
            T_CLASS,
            T_CLONE,
            T_CONST,
            T_CONTINUE,
            T_DEFAULT,
            T_DO,
            T_ECHO,
            T_ELSE,
            T_ELSEIF,
            T_EXTENDS,
            T_FINAL,
            T_FINALLY,
            T_FN,
            T_FOR,
            T_FOREACH,
            T_FUNCTION,
            T_GLOBAL,
            T_GOTO,
            T_IF,
            T_IMPLEMENTS,
            T_INTERFACE,
            T_MATCH,
            T_NAMESPACE,
            T_NEW,
            T_PRIVATE,
            T_PROTECTED,
            T_PUBLIC,
            T_READONLY,
            T_RETURN,
            T_STATIC,
            T_SWITCH,
            T_THROW,
            T_TRAIT,
            T_TRY,
            T_USE,
            T_WHILE,
            T_YIELD,
            T_YIELD_FROM => 'lp-src-keyword',
            T_STRING,
            T_NAME_FULLY_QUALIFIED,
            T_NAME_QUALIFIED,
            T_NAME_RELATIVE => 'lp-src-name',
            T_VARIABLE => 'lp-src-variable',
            T_CONSTANT_ENCAPSED_STRING,
            T_ENCAPSED_AND_WHITESPACE => 'lp-src-string',
            T_LNUMBER,
            T_DNUMBER => 'lp-src-number',
            T_COMMENT,
            T_DOC_COMMENT => 'lp-src-comment',
            T_OPEN_TAG,
            T_CLOSE_TAG => 'lp-src-tag',
            default => 'lp-src-text',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
