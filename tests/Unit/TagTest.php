<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Tag;

class TagTest extends TestCase
{
    public function testTrimsTrailingCommas()
    {
        $input = 'tag,';
        $tag = Tag::normalize($input);
        $this->assertEquals('tag', $tag);

        $input = 'tag, ';
        $tag = Tag::normalize($input);
        $this->assertEquals('tag', $tag);
    }

    public function testNoDoubleHyphen()
    {
        $input = 'one**two';
        $tag = Tag::normalize($input);
        $this->assertEquals('one-two', $tag);

        $input = 'one***two';
        $tag = Tag::normalize($input);
        $this->assertEquals('one-two', $tag);
    }

    public function testToLowercase()
    {
        $input = 'TagName';
        $tag = Tag::normalize($input);
        $this->assertEquals('tagname', $tag);
    }

    public function testToLowercaseNonAscii()
    {
        $input = 'Düsseldorf';
        $tag = Tag::normalize($input);
        $this->assertEquals('düsseldorf', $tag);

        $input = 'Çelik';
        $tag = Tag::normalize($input);
        $this->assertEquals('çelik', $tag);
    }

    public function testLettersFromAnyScript()
    {
        $this->assertEquals('москва', Tag::normalize('Москва'));
        $this->assertEquals('東京', Tag::normalize('東京'));
        $this->assertEquals('größe', Tag::normalize('Größe'));
        $this->assertEquals('नमस्ते', Tag::normalize('नमस्ते'));
        $this->assertEquals('open-source-東京', Tag::normalize('Open Source / 東京'));
    }

    public function testTheSameTagAlwaysNormalizesTheSameWay()
    {
        // "é" written as one character, and as "e" followed by a combining accent
        $this->assertEquals(Tag::normalize("caf\u{00E9}"), Tag::normalize("cafe\u{0301}"));
    }

    public function testExistingTagsAreUnchanged()
    {
        // Tags saved with the previous, narrower rules normalize to themselves
        foreach(['indieweb', 'one-two', 'düsseldorf', 'café-société', 'grö-e', 'iwc-2031'] as $tag) {
            $this->assertEquals($tag, Tag::normalize($tag));
        }
    }

    public function testTextThatIsNotValidUtf8()
    {
        $this->assertSame('', Tag::normalize("\xff\xfe"));
    }
}
