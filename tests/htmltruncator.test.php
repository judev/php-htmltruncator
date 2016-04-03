<?php

use HtmlTruncator\Truncator;

class HtmlTruncatorTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->short_text = "<p>Foo <b>Bar</b> Baz</p>";
		$this->long_text = "<p>Foo ".str_repeat("<b>Bar Baz</b> ", 100)."Quux</p>";
		$this->list_text = "<p>Foo:</p><ul>".str_repeat("<li>Bar Baz</li>", 100)."</ul>";
	}

	public function testDoesNotModifyShortText() {
		$this->assertEquals($this->short_text, Truncator::truncate($this->short_text, 10));
	}

	public function testTruncatesLongTextToGivenNumberOfWords() {
		$nwords = str_word_count(strip_tags(Truncator::truncate($this->long_text, 10, '')));
		$this->assertEquals(10, $nwords);
		$nwords = str_word_count(strip_tags(Truncator::truncate($this->long_text, 11, '')));
		$this->assertEquals(11, $nwords);
	}

	public function testDoesNotContainEmptyNodes() {
		$this->assertNotRegExp('/<b>\s*<\/b>/', Truncator::truncate($this->long_text, 10, '...'));
		$this->assertNotRegExp('/<b>\s*<\/b>/', Truncator::truncate($this->long_text, 11, '...'));
		$this->assertNotRegExp('/<li>\s*<\/li>/', Truncator::truncate($this->list_text, 10, '...'));
		$this->assertNotRegExp('/<li>\s*<\/li>/', Truncator::truncate($this->list_text, 11, '...'));
	}

	public function testEllipsisInsideLastNode() {
		$this->assertRegExp('/\.\.\.<\/li>\s*<\/ul>$/', Truncator::truncate($this->list_text, 10, '...'));
	}

	public function testEllipsisAsLastOptionsArgument() {
		$this->assertEquals("<p>Foo <b>Bar Baz</b>...</p>", Truncator::truncate($this->long_text, 3, array('ellipsis' => "...")));
		$this->assertEquals("<p>Foo <b>Bar Baz</b> <b>Bar</b>...</p>", Truncator::truncate($this->long_text, 4, array('ellipsis' => "...")));
	}

	public function testEllipsisAsLastArgument() {
		$this->assertEquals("<p>Foo <b>Bar Baz</b>...</p>", Truncator::truncate($this->long_text, 3, "..."));
		$this->assertEquals("<p>Foo <b>Bar Baz</b> <b>Bar</b>...</p>", Truncator::truncate($this->long_text, 4, "..."));
	}

	public function testTruncatesLongText() {
		$this->assertEquals("<p>Foo <b>Bar Baz</b>...</p>", Truncator::truncate($this->long_text, 3, "..."));
		$this->assertEquals("<p>Foo <b>Bar Baz</b> <b>Bar</b>...</p>", Truncator::truncate($this->long_text, 4, "..."));
		$this->assertEquals("<p>Foo:</p><ul><li>Bar Baz</li><li>...</li></ul>", Truncator::truncate($this->list_text, 3, '...'));
		$this->assertEquals("<p>Foo:</p><ul><li>Bar Baz</li><li>Bar...</li></ul>", Truncator::truncate($this->list_text, 4, '...'));
	}

	public function testPreservesWhitespaceInTextContent() {
		$text = "<p>Foo ".str_repeat("<b>Bar\nBaz</b> ", 100)."Quux</p>";
		$this->assertEquals("<p>Foo <b>Bar\nBaz</b> <b>Bar</b>...</p>", Truncator::truncate($text, 4, '...'));
	}

	public function testPreservesWhitespaceBetweenElements() {
		$text = "<div>".str_repeat("<p>Bar\nBaz</p> ", 100)."</div>";
		$this->assertEquals("<div><p>Bar\nBaz</p> <p>Bar...</p></div>", Truncator::truncate($text, 3, '...'));
		$text = "<ul>".str_repeat("<li>Bar\nBaz</li>\n", 100)."</ul>";
		$this->assertEquals("<ul><li>Bar\nBaz</li>\n<li>Bar...</li></ul>", Truncator::truncate($text, 3, '...'));
	}

	public function testAllowsHtmlEllipsis() {
		$this->assertEquals('<p>Foo <b>Bar</b> <a href="/more">...</a></p>', Truncator::truncate($this->long_text, 2, array('ellipsis' => ' <a href="/more">...</a>')));
	}

	public function testWorksWithPre() {
		$this->assertEquals("<p>foo bar</p><pre>foo</pre>...", Truncator::truncate("<p>foo bar</p><pre>foo bar</pre>", 3, "..."));
	}

	public function testMarkTagAsEllipsable() {
		$this->assertEquals( "<blockquote>Foo bar baz</blockquote>...", Truncator::truncate("<blockquote>Foo bar baz quux</blockquote>", 3, "..."));
		Truncator::$ellipsable_tags[] = 'blockquote';
		$this->assertEquals( "<blockquote>Foo bar baz...</blockquote>", Truncator::truncate("<blockquote>Foo bar baz quux</blockquote>", 3, "..."));
	}

	public function testHandlesHTML5UsingXMLParser() {
		$txt = "<article><ul><li>Foo Bar</li><li>baz quux</li></ul></article>";
		$truncated = Truncator::truncate($txt, 3, array('ellipsis' => "...", 'xml' => true));
		$this->assertEquals("<article><ul><li>Foo Bar</li><li>baz...</li></ul></article>", $truncated);
	}

	public function testHandlesHTML5UsingHTML5Lib() {
		require('vendor/autoload.php');
		$txt = "<article><ul><li>Foo Bar</li><li>baz quux</li></ul></article>";
		$truncated = Truncator::truncate($txt, 3, array('ellipsis' => "...",));
		$this->assertEquals("<article><ul><li>Foo Bar</li><li>baz...</li></ul></article>", $truncated);
	}

	public function testHandlesDeepNesting() {
		$txt = "<article><ul><li>Foo Bar</li><li><b><u><s>baz</s> quux</u></b></li></ul></article>";
		$truncated = Truncator::truncate($txt, 3, array('ellipsis' => "...", 'xml' => true));
		$this->assertEquals("<article><ul><li>Foo Bar</li><li><b><u><s>baz</s></u></b>...</li></ul></article>", $truncated);
	}

	public function testDefaultEllipsis() {
		$this->assertEquals("<p>Foo <b>Bar Baz</b>…</p>", Truncator::truncate($this->long_text, 3));
	}

	public function testTruncateWithCharacterLength() {
		$this->assertEquals("<p>Foo <b>Bar Baz</b>...</p>", Truncator::truncate($this->long_text, 11, array('ellipsis' => "...", 'length_in_chars' => true)));
		$this->assertEquals("<p>Foo <b>Bar Baz</b> <b>Bar</b>...</p>", Truncator::truncate($this->long_text, 15, array('ellipsis' => "...", 'length_in_chars' => true)));
	}

	public function testTruncateWithCharacterLengthToLastWordBeforeLimit() {
		$this->assertEquals("<p>Foo...</p>", Truncator::truncate($this->long_text, 5, array('ellipsis' => "...", 'length_in_chars' => true)));
		$this->assertEquals("<p>Foo <b>Bar</b>...</p>", Truncator::truncate($this->long_text, 10, array('ellipsis' => "...", 'length_in_chars' => true)));
		$this->assertEquals("<p>Foo <b>Bar Baz</b>...</p>", Truncator::truncate($this->long_text, 14, array('ellipsis' => "...", 'length_in_chars' => true)));
	}

	public function testAlwaysTruncateByCharacterLengthIfShort() {
		$this->assertEquals("<p>F...</p>", Truncator::truncate($this->long_text, 1, array('ellipsis' => "...", 'length_in_chars' => true)));
		$this->assertEquals("<p>Honor...</p>", Truncator::truncate("<p>Honorificabilitudinitatibus</p>", 5, array('ellipsis' => "...", 'length_in_chars' => true)));
	}

	public function testDoesNotEscapeHtmlEntities() {
		$txt = "
<p>Dans le wiki, le \"titre\" <strong>Log des modifications</strong> s'étale sur toute la largeur de l'écran et la barre de saisie dépasse allégrement la taille dudit écran.<br />
Je pense qu'ils devraient être en fait même sur la même ligne… Sans doute un problème de <em>float</em> ou de <em>display</em>.</p>

<p>Dans les journaux (et sans doute partout ailleurs) <strong>Sujet du commentaire</strong> aussi s'étale trop.</p>

<p>Les paragraphes ne sont pas séparés. Il faut rajouter des <em>margin</em> à &lt;p&gt;…&lt;/p&gt;, sinon quoi les sauts de ligne et retours à la ligne sont indistinguables.</p>
";
		$this->assertRegExp('/&lt;p&gt;…&lt;\/p&gt;/', Truncator::truncate($txt, 80));
	}

	public function testKeepsSpacesAfterLinks() {
		$txt = "
<p>Depuis 1995 l'humanité est véritablement entrée dans une nouvelle ère. Alors que, depuis l'aube des temps, nous ne savions pas si d'autres planètes existaient autour des étoiles lointaines, voilà que soudain la première d'entre elle était découverte en orbite autour de <a href=\"http://fr.wikipedia.org/wiki/51_Pegasi\">51 Pegasi</a>.<br />
Après 2 500 ans de spéculations nous avions enfin une réponse ! Six siècles après la condamnation à mort de <a href=\"http://fr.wikipedia.org/wiki/Giordano_Bruno\">Giordano Bruno</a> nous savions enfin qu'il avait eu raison ! <a href=\"http://fr.wikipedia.org/wiki/Exoplan%C3%A8te\">Les exoplanètes</a> existent bel et bien et notre système solaire n'est pas une exception cosmique.</p>
";
		$this->assertRegExp('/Les exoplanètes<\/a> existent/', Truncator::truncate($txt, 80));
	}

	public function testPreservesBRTags() {
		$txt = "
<p>Bonjour</p>
<blockquote>
On 11/06/11 11:12, JP wrote:<br />
&gt; Nom : JP<br />
&gt; Message : Problème<br />
</blockquote>
";
		$this->assertRegExp('/wrote:<br ?\/>/', Truncator::truncate($txt, 10));
	}

	public function testPreservesIMGTags() {
		$txt = "
<p>Bonjour</p>
<img src=\"/foo.png\" />
<p>Foo bar baz</p>
";
		$this->assertRegExp('/<img src="\/foo.png"/', Truncator::truncate($txt, 2));
	}

	public function testTruncateMethodDoesNotSkipWords()
	{
		$sample = '<p>The Arcus Foundation latest effort in support of legal issues.</p>';
		$charLength = 30;

		$result = Truncator::truncate($sample, $charLength, array('length_in_chars' => true));

		$expectedResult = '<p>The Arcus Foundation latest…</p>';

		$this->assertSame($expectedResult, $result);
	}

	public function testRemovesPunctuationBeforeEllipsis() {
		$sample = "<p>Foo! <b>Bar</b> Baz</p>";
		$expectedResult = "<p>Foo…</p>";
		$length = 1;
		$result = Truncator::truncate($sample, $length);
		$this->assertSame($expectedResult, $result);
	}

	public function testHandleHtmlComments()
	{
		$sample = '<div><!--<a href="#">test</a>--><p>Lorem Ipsum</p></div>';
		$charLength = 100;

		$result = Truncator::truncate($sample, $charLength, array('length_in_chars' => true));

		$expectedResult = '<div><p>Lorem Ipsum</p></div>';

		$this->assertSame($expectedResult, $result);
	}

	public function testFiltersBadUtf8() {
		$sample = '<p>Lorem'.chr(27).'Ipsum Dolor</p>';
		$length = 2;
		$result = Truncator::truncate($sample, $length);
		$expectedResult = '<p>Lorem Ipsum…</p>';
		$this->assertSame($expectedResult, $result);
	}

	public function testDoesNotBreakEntities() {
		$sample = '<p>Foo &gt; Bar</p>';
		$length = 2;
		$result = Truncator::truncate($sample, $length);
		$expectedResult = '<p>Foo…</p>';
		$this->assertSame($expectedResult, $result);
	}

	public function testUnicodeDoesntBreak() {
		$sample = '<p>This is the greek letter beta: β</p>';
		$length = 7;
		$result = Truncator::truncate($sample, $length);
		$expectedResult = $sample;
		$this->assertSame($expectedResult, $result);
	}

}

