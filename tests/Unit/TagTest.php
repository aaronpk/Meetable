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
}
