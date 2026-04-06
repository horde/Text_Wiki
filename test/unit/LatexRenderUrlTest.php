<?php

namespace Horde\Text\Wiki\Test\Unit;

use Horde\Text\Wiki\TextWikiBase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test LaTeX URL rendering
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */
#[CoversNothing]
class LatexRenderUrlTest extends TestCase
{
    public function testRenderUrlToLatex(): void
    {
        $wiki = TextWikiBase::factory('Default', ['Url']);

        $input = '
[http://www.example.com/page An example page]
http://www.example.com/page
';

        $expected = '\documentclass{article}
\usepackage{ulem}
\pagestyle{headings}
\begin{document}

An example page\footnote{http://www.example.com/page}
http://www.example.com/page\footnote{http://www.example.com/page}
\end{document}
';

        $this->assertEquals($expected, $wiki->transform($input, 'Latex'));
    }
}
