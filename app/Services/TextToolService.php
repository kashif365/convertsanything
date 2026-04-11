<?php

namespace App\Services;

class TextToolService
{
    public function analyze(string $text): array
    {
        $trimmed = trim($text);
        $words = $trimmed === '' ? 0 : count(preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
        $sentences = $trimmed === '' ? 0 : count(preg_split('/[.!?]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
        $paragraphs = $trimmed === '' ? 0 : count(preg_split("/\R{2,}/", $trimmed, -1, PREG_SPLIT_NO_EMPTY));

        return [
            ['label' => 'Words', 'value' => $words],
            ['label' => 'Characters', 'value' => mb_strlen($text)],
            ['label' => 'No Spaces', 'value' => mb_strlen(preg_replace('/\s+/u', '', $text) ?? '')],
            ['label' => 'Sentences', 'value' => $sentences],
            ['label' => 'Paragraphs', 'value' => $paragraphs],
            ['label' => 'Reading Time', 'value' => $words ? max(1, (int) ceil($words / 225)).' min' : '0 min'],
            ['label' => 'Speaking Time', 'value' => $words ? max(1, (int) ceil($words / 150)).' min' : '0 min'],
        ];
    }

    public function convertCase(string $text, string $mode): string
    {
        return match ($mode) {
            'upper' => mb_strtoupper($text),
            'lower' => mb_strtolower($text),
            'sentence' => preg_replace_callback('/(^\s*\pL|[.!?]\s+\pL)/u', fn ($m) => mb_strtoupper($m[0]), mb_strtolower($text)) ?? mb_strtolower($text),
            'title' => mb_convert_case(mb_strtolower($text), MB_CASE_TITLE),
            'capitalize' => $text === '' ? $text : mb_strtoupper(mb_substr($text, 0, 1)).mb_strtolower(mb_substr($text, 1)),
            'alternating' => $this->alternating($text),
            'inverse' => $this->inverse($text),
            default => $text,
        };
    }

    private function alternating(string $text): string
    {
        $upper = false;
        $result = '';

        foreach (mb_str_split($text) as $char) {
            if (preg_match('/\pL/u', $char)) {
                $upper = ! $upper;
                $result .= $upper ? mb_strtoupper($char) : mb_strtolower($char);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    private function inverse(string $text): string
    {
        $result = '';

        foreach (mb_str_split($text) as $char) {
            $upper = mb_strtoupper($char);
            $lower = mb_strtolower($char);
            $result .= $char === $upper ? $lower : $upper;
        }

        return $result;
    }
}
