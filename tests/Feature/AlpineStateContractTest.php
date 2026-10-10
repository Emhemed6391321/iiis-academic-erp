<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * An Alpine directive that is a bare identifier (x-if="isSuperAdminUser") must refer to something the
 * dashboard actually defines, otherwise every render throws "X is not defined" in the browser.
 */
class AlpineStateContractTest extends TestCase
{
    public function test_bare_identifiers_in_alpine_directives_are_defined_somewhere(): void
    {
        $texts = [];
        foreach ((new Finder())->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $texts[$file->getFilename()] = $file->getContents();
        }
        $all = implode("\n", $texts);

        $defined = [];
        // object keys / methods / getters / async methods
        preg_match_all('/(?:^|[\s,{])(?:async\s+|get\s+|set\s+)?([A-Za-z_$][\w$]*)\s*(?::|\()/m', $all, $m);
        $defined = array_merge($defined, $m[1]);
        // local variables and x-for aliases
        preg_match_all('/\b(?:const|let|var)\s+([A-Za-z_$][\w$]*)/', $all, $m);
        $defined = array_merge($defined, $m[1]);
        preg_match_all('/x-for="\s*\(?\s*([A-Za-z_$][\w$]*)(?:\s*,\s*([A-Za-z_$][\w$]*))?\s*\)?\s+in/', $all, $m);
        $defined = array_merge($defined, $m[1], array_filter($m[2]));
        $defined = array_flip(array_merge($defined, ['true', 'false', 'null', 'undefined', 'window', 'document', 'navigator']));

        $missing = [];
        foreach ($texts as $name => $text) {
            preg_match_all('/(?:x-if|x-show|x-text|x-html)="\s*!?\s*([A-Za-z_$][\w$]*)\s*"/', $text, $matches);
            foreach ($matches[1] as $ident) {
                if (!isset($defined[$ident])) {
                    $missing[$ident][] = $name;
                }
            }
        }

        $this->assertSame([], $missing, 'Alpine identifiers used but never defined: ' . json_encode($missing, JSON_UNESCAPED_UNICODE));
    }
}
