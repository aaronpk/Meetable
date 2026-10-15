<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The newer and older links at the bottom of a paged archive.
 */
class PaginationViewTest extends TestCase
{
    private function render(array $page)
    {
        return trim(view('components/pagination', ['page' => $page])->render());
    }

    public function testBothDirectionsAreLinks()
    {
        $html = $this->render(['newer' => '/archive?after=x', 'older' => '/archive?before=y']);

        $this->assertStringContainsString('<a class="pagination-previous" href="/archive?after=x" rel="prev">Newer events</a>', $html);
        $this->assertStringContainsString('<a class="pagination-next" href="/archive?before=y" rel="next">Older events</a>', $html);
    }

    public function testAMissingDirectionIsShownDisabled()
    {
        $html = $this->render(['newer' => null, 'older' => '/archive?before=y']);

        $this->assertStringContainsString('<span class="pagination-previous is-disabled">Newer events</span>', $html);
        $this->assertStringContainsString('rel="next"', $html);
    }

    public function testASinglePageHasNoNavigation()
    {
        $this->assertEquals('', $this->render(['newer' => null, 'older' => null]));
    }
}
