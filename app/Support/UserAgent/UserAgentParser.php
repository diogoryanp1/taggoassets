<?php

namespace App\Support\UserAgent;

class UserAgentParser
{
    public function parse(?string $ua): ParsedUserAgent
    {
        $ua = (string) $ua;
        $browser = str_contains($ua, 'Edg/') ? 'Edge' : (str_contains($ua, 'Firefox/') ? 'Firefox' : (str_contains($ua, 'Chrome/') ? 'Chrome' : (str_contains($ua, 'Safari/') ? 'Safari' : 'Desconhecido')));
        $os = str_contains($ua, 'iPhone') ? 'iPhone' : (str_contains($ua, 'Android') ? 'Android' : (str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Linux') ? 'Linux' : 'Desconhecido')));

        return new ParsedUserAgent($browser, $os, str_contains($ua, 'Mobile') || str_contains($ua, 'iPhone') ? 'mobile' : 'desktop');
    }
}
