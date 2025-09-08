<?php

namespace App\Services;

/**
 * Corta texto largo en "chunks" (ventanas) con solapamiento.
 * - Usa mb_* para soportar UTF-8 (acentos, ñ, etc).
 * - Intenta respetar párrafos y oraciones; si no entra, parte por caracteres.
 */
class Chunker
{
    /**
     * @param string $text      Texto completo.
     * @param int    $max       Máx. caracteres por chunk (ej. 900).
     * @param int    $overlap   Solapamiento entre chunks (ej. 120).
     * @return array<int,string>
     */
    public function make(string $text, int $max = 900, int $overlap = 120): array
    {
        $text = $this->normalize($text);
        if ($text === '') return [];

        // separa por párrafos (líneas en blanco)
        $paras = preg_split("/\n{2,}/u", $text) ?: [$text];

        $out  = [];
        $buf  = '';
        $tail = '';

        foreach ($paras as $p) {
            $p = trim(preg_replace("/\s+/u", ' ', $p) ?? '');
            if ($p === '') continue;

            // Si el párrafo es más grande que $max, lo partimos en oraciones o por caracteres
            if (mb_strlen($p) > $max) {
                foreach ($this->splitSentences($p) as $sent) {
                    if ($sent === '') continue;

                    if ($buf === '') {
                        // iniciamos un nuevo chunk con el tail (solapamiento)
                        if ($tail !== '') {
                            $buf = $tail;
                            $tail = '';
                        }
                    }

                    if (mb_strlen($buf) + 1 + mb_strlen($sent) <= $max) {
                        $buf = $buf === '' ? $sent : $buf . ' ' . $sent;
                    } else {
                        // cerramos chunk actual
                        if ($buf !== '') {
                            $out[] = trim($buf);
                            $tail  = $this->rightTail($buf, $overlap);
                            $buf   = '';
                        }
                        // si la oración sola es > max, cortar duro por chars
                        if (mb_strlen($sent) > $max) {
                            foreach ($this->hardSplit($sent, $max) as $piece) {
                                // nuevo chunk con tail si cabe
                                $buf = ($tail !== '' ? $tail . ' ' : '') . $piece;
                                $out[] = trim(mb_substr($buf, 0, $max));
                                $tail  = $this->rightTail($buf, $overlap);
                                $buf   = '';
                            }
                        } else {
                            // arrancamos chunk nuevo con tail + esta oración
                            $buf = ($tail !== '' ? $tail . ' ' : '') . $sent;
                            $tail = '';
                        }
                    }
                }
            } else {
                // párrafo corto: intentar agregarlo
                if ($buf === '') {
                    if ($tail !== '') {
                        $buf = $tail;
                        $tail = '';
                    }
                }
                if (mb_strlen($buf) + 1 + mb_strlen($p) <= $max) {
                    $buf = $buf === '' ? $p : $buf . ' ' . $p;
                } else {
                    // cerramos y empezamos nuevo con tail
                    if ($buf !== '') {
                        $out[] = trim($buf);
                        $tail  = $this->rightTail($buf, $overlap);
                        $buf   = '';
                    }
                    $buf = ($tail !== '' ? $tail . ' ' : '') . $p;
                    $tail = '';
                }
            }
        }

        if ($buf !== '') {
            $out[] = trim($buf);
        }

        return $out;
    }

    protected function normalize(string $t): string
    {
        // normaliza saltos y espacios
        $t = str_replace("\r\n", "\n", $t);
        $t = preg_replace("/[ \t]+/u", ' ', $t) ?? $t;
        // compacta saltos múltiples pero deja doble salto para separar párrafos
        $t = preg_replace("/\n{3,}/u", "\n\n", $t) ?? $t;
        return trim($t);
    }

    /**
     * Divide en oraciones usando puntuación española.
     */
    protected function splitSentences(string $t): array
    {
        $t = trim($t);
        if ($t === '') return [];

        // Corta después de . ! ? y antes de mayúscula / signos ¿¡
        $parts = preg_split('/(?<=[\.\!\?])\s+(?=[\p{Lu}¡¿])/u', $t) ?: [$t];

        // Si quedó demasiado largo algún trozo, lo dejamos; se corta más arriba si es necesario
        return array_map('trim', $parts);
    }

    /**
     * Corta un texto largo por caracteres duros (fallback).
     */
    protected function hardSplit(string $t, int $max): array
    {
        $out = [];
        $len = mb_strlen($t);
        for ($i = 0; $i < $len; $i += $max) {
            $out[] = trim(mb_substr($t, $i, $max));
        }
        return $out;
    }

    /**
     * Devuelve la "cola" (tail) para solapamiento.
     */
    protected function rightTail(string $t, int $overlap): string
    {
        $L = mb_strlen($t);
        if ($overlap <= 0 || $L === 0) return '';
        $start = max(0, $L - $overlap);
        return trim(mb_substr($t, $start));
    }
}
