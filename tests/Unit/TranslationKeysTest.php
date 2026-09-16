<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A translation key that doesn't exist is shown on the page as the key itself, so check
 * every key used in the code is in the English language files.
 */
class TranslationKeysTest extends TestCase
{
    private function sourceFiles()
    {
        return (new Finder)->files()->name('*.php')->in([app_path(), resource_path('views')]);
    }

    public function testEveryKeyUsedExistsInEnglish()
    {
        $missing = [];

        foreach($this->sourceFiles() as $file) {
            $source = $file->getContents();

            preg_match_all("/(?:__|trans_choice|@lang|Lang::get)\\(\\s*'([a-z_]+\\.[A-Za-z0-9_.]+)'/", $source, $matches);
            foreach($matches[1] as $key) {
                if(substr($key, -1) != '.' && !Lang::has($key, 'en', false))
                    $missing[] = $key.' in '.$file->getRelativePathname();
            }

            preg_match_all("/Dates::format\\([^;]*?,\\s*'([a-z_]+)'\\)/", $source, $matches);
            foreach($matches[1] as $key) {
                if(!Lang::has('dates.'.$key, 'en', false))
                    $missing[] = 'dates.'.$key.' in '.$file->getRelativePathname();
            }
        }

        $this->assertEquals([], array_values(array_unique($missing)));
    }

    public function testNoEnglishStringIsEmpty()
    {
        $empty = [];

        foreach(glob(resource_path('lang/en/*.php')) as $path) {
            $group = basename($path, '.php');
            $strings = Lang::get($group, [], 'en');
            array_walk_recursive($strings, function($value, $key) use($group, &$empty) {
                if(!is_string($value) || trim($value) === '')
                    $empty[] = $group.'.'.$key;
            });
        }

        $this->assertEquals([], $empty);
    }
}
