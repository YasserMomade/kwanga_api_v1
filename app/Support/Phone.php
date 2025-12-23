<?php

namespace App\Support;

class Phone
{
    public static function normalizeMoz(string $phone): string
    {
        // Remove espaços, hifens e virgulas
        $p = str_replace([' ', '-', ','], '', $phone);

        // Se começa com 00 → troca por +
        if (str_starts_with($p, '00')) {
            $p = '+' . substr($p, 2);
        }

        // Se começa com +258 → ok
        if (str_starts_with($p, '+258')) {
            return $p;
        }

        // Se comeca com 258 sem +
        if (str_starts_with($p, '258')) {
            return '+' . $p;
        }

        // Se começa com 8 e tem 9 digitos
        if (preg_match('/^8\d{8}$/', $p)) {
            return '+258' . $p;
        }

        return $p;
    }
}
