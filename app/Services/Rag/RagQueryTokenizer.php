<?php

namespace App\Services\Rag;

class RagQueryTokenizer
{
    /** @var list<string> */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'what', 'how', 'does', 'are', 'can', 'you', 'our', 'this', 'that', 'with',
        'do', 'know', 'who', 'tell', 'about', 'have', 'any', 'there', 'from', 'your', 'project',
    ];

    /** @var list<string> */
    private const DATA_QUERY_NEEDLES = [
        'startup', 'application', 'founder', 'portfolio', 'applied', 'submitted', 'company', 'pitch',
        'know', 'who', 'risk', 'flag', 'strength', 'weakness', 'score', 'screening',
        // Arabic (common incubator / screening questions)
        'نقاط', 'قوة', 'قوات', 'مؤشر', 'ضعف', 'عيوب', 'مخاطر', 'مؤسس', 'مشروع', 'شركة', 'تقييم', 'فحص',
    ];

    /**
     * Latin-script name phrases (e.g. "Palestinian Takke") embedded in Arabic or English questions.
     *
     * @return list<string>
     */
    public static function latinNamePhrases(string $question): array
    {
        if (! preg_match_all('/[A-Za-z][A-Za-z0-9\'&.-]*(?:\s+[A-Za-z][A-Za-z0-9\'&.-]+)*/u', $question, $matches)) {
            return [];
        }

        $phrases = [];
        foreach ($matches[0] as $match) {
            $phrase = trim(preg_replace('/\s+/u', ' ', $match) ?? $match);
            if (mb_strlen($phrase) >= 3) {
                $phrases[] = $phrase;
            }
        }

        return array_values(array_unique($phrases));
    }

    /**
     * @return list<string>
     */
    public static function latinWordTokens(string $question): array
    {
        $tokens = [];
        foreach (self::latinNamePhrases($question) as $phrase) {
            foreach (preg_split('/\s+/u', $phrase) ?: [] as $word) {
                $word = trim($word);
                if (mb_strlen($word) >= 3 && ! in_array(mb_strtolower($word), self::STOP_WORDS, true)) {
                    $tokens[] = $word;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @return list<string>
     */
    public static function searchTokens(string $question): array
    {
        $words = preg_split('/\W+/u', $question) ?: [];
        $tokens = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 3) {
                continue;
            }
            if (in_array(mb_strtolower($word), self::STOP_WORDS, true)) {
                continue;
            }
            $tokens[] = $word;
        }

        return array_slice(array_values(array_unique($tokens)), 0, 6);
    }

    public static function looksLikeDataQuery(string $question): bool
    {
        $q = mb_strtolower($question);
        foreach (self::DATA_QUERY_NEEDLES as $needle) {
            if (str_contains($q, mb_strtolower($needle))) {
                return true;
            }
        }

        return count(self::latinWordTokens($question)) >= 2
            || count(self::searchTokens($question)) >= 2;
    }

    public static function asksAboutScreeningAttributes(string $question): bool
    {
        $q = mb_strtolower($question);
        foreach ([
            'strength', 'weakness', 'risk', 'flag', 'score', 'screen', 'recommend',
            'نقاط', 'قوة', 'ضعف', 'عيوب', 'مخاطر', 'تقييم', 'فحص',
        ] as $needle) {
            if (str_contains($q, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
