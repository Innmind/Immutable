<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Str as S,
    Sequence,
    Map,
    Set,
    Exception\InvalidRegex,
};
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testInterfaces()
    {
        $str = S::of('foo');

        $this->assertSame('foo', $str->toString());
    }

    public function testOf()
    {
        $str = S::of('foo', 'ASCII');

        $this->assertInstanceOf(S::class, $str);
        $this->assertSame('foo', $str->toString());
        $this->assertSame('ASCII', $str->encoding()->toString());
    }

    public function testEncoding()
    {
        $this->assertInstanceOf(S::class, S::of('')->encoding());
        $this->assertSame('UTF-8', S::of('')->encoding()->toString());
    }

    public function testToEncoding()
    {
        $str = S::of('foo🙏bar');
        $str2 = $str->toEncoding('ASCII');
        $str3 = $str->toEncoding(S\Encoding::ascii);

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('UTF-8', $str->encoding()->toString());
        $this->assertSame('ASCII', $str2->encoding()->toString());
        $this->assertSame('ASCII', $str3->encoding()->toString());
        $this->assertSame(7, $str->length());
        $this->assertSame(10, $str2->length());
        $this->assertSame(10, $str3->length());
    }

    public function testSplit()
    {
        $str = S::of('foo');

        $sequence = $str->split();
        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertCount(3, $sequence);

        foreach ($sequence->toList() as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', $this->get($sequence, 0)->toString());
        $this->assertSame('o', $this->get($sequence, 1)->toString());
        $this->assertSame('o', $this->get($sequence, 2)->toString());

        $parts = S::of('🤩👍🤔', 'UTF-8')->split();

        $this->assertSame('🤩', $this->get($parts, 0)->toString());
        $this->assertSame('👍', $this->get($parts, 1)->toString());
        $this->assertSame('🤔', $this->get($parts, 2)->toString());
        $this->assertNotSame(
            '🤩',
            $this->get(S::of('🤩👍🤔', 'ASCII')->split(), 0)->toString(),
        );

        $sequence = $str->split('');
        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertCount(3, $sequence);

        foreach ($sequence->toList() as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', $this->get($sequence, 0)->toString());
        $this->assertSame('o', $this->get($sequence, 1)->toString());
        $this->assertSame('o', $this->get($sequence, 2)->toString());

        $str = S::of('f|o|o');
        $sequence = $str->split('|');
        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertCount(3, $sequence);

        foreach ($sequence->toList() as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', $this->get($sequence, 0)->toString());
        $this->assertSame('o', $this->get($sequence, 1)->toString());
        $this->assertSame('o', $this->get($sequence, 2)->toString());
    }

    public function testSplitOnZeroString()
    {
        $parts = S::of('10101')->split('0');

        $this->assertCount(3, $parts);
        $this->assertSame('1', $this->get($parts, 0)->toString());
        $this->assertSame('1', $this->get($parts, 1)->toString());
        $this->assertSame('1', $this->get($parts, 2)->toString());
    }

    public function testSplitUtf8ManipulatedAsAscii()
    {
        $str = S::of('foo🙏bar');
        $splits = $str->split();

        $this->assertSame('f', $this->get($splits, 0)->toString());
        $this->assertSame('o', $this->get($splits, 1)->toString());
        $this->assertSame('o', $this->get($splits, 2)->toString());
        $this->assertSame('🙏', $this->get($splits, 3)->toString());
        $this->assertSame('b', $this->get($splits, 4)->toString());
        $this->assertSame('a', $this->get($splits, 5)->toString());
        $this->assertSame('r', $this->get($splits, 6)->toString());

        $splits = $str->toEncoding('ASCII')->split();

        $this->assertSame('f', $this->get($splits, 0)->toString());
        $this->assertSame('o', $this->get($splits, 1)->toString());
        $this->assertSame('o', $this->get($splits, 2)->toString());
        $this->assertSame(
            '🙏',
            $this->get($splits, 3)->toString().$this->get($splits, 4)->toString().$this->get($splits, 5)->toString().$this->get($splits, 6)->toString(),
        );
        $this->assertSame('b', $this->get($splits, 7)->toString());
        $this->assertSame('a', $this->get($splits, 8)->toString());
        $this->assertSame('r', $this->get($splits, 9)->toString());
    }

    public function testSplitUtf8ManipulatedAsAsciiWithDelimiter()
    {
        $str = S::of('foo🙏bar');
        $splits = $str->split('🙏');

        $this->assertSame('foo', $this->get($splits, 0)->toString());
        $this->assertSame('bar', $this->get($splits, 1)->toString());

        $splits = $str->toEncoding('ASCII')->split('🙏');

        $this->assertSame('foo', $this->get($splits, 0)->toString());
        $this->assertSame('bar', $this->get($splits, 1)->toString());

        $splits = $str->toEncoding('ASCII')->split(
            \mb_substr('🙏', 0, 1, 'ASCII'),
        );

        $this->assertSame('foo', $this->get($splits, 0)->toString());
        $this->assertSame(
            \mb_substr('🙏', 1, null, 'ASCII').'bar',
            $this->get($splits, 1)->toString(),
        );
    }

    public function testChunk()
    {
        $str = S::of('foobarbaz');

        $sequence = $str->chunk(4);
        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertInstanceOf(S::class, $this->get($sequence, 0));
        $this->assertInstanceOf(S::class, $this->get($sequence, 1));
        $this->assertInstanceOf(S::class, $this->get($sequence, 2));
        $this->assertSame('foob', $this->get($sequence, 0)->toString());
        $this->assertSame('arba', $this->get($sequence, 1)->toString());
        $this->assertSame('z', $this->get($sequence, 2)->toString());
    }

    public function testChunkUtf8ManipulatedAsAscii()
    {
        $splits = S::of('foo🙏bar')
            ->toEncoding('ASCII')
            ->chunk();

        $this->assertSame('f', $this->get($splits, 0)->toString());
        $this->assertSame('o', $this->get($splits, 1)->toString());
        $this->assertSame('o', $this->get($splits, 2)->toString());
        $this->assertSame(
            '🙏',
            $this->get($splits, 3)->toString().$this->get($splits, 4)->toString().$this->get($splits, 5)->toString().$this->get($splits, 6)->toString(),
        );
        $this->assertSame('b', $this->get($splits, 7)->toString());
        $this->assertSame('a', $this->get($splits, 8)->toString());
        $this->assertSame('r', $this->get($splits, 9)->toString());

        $splits = S::of('foo🙏bar')
            ->toEncoding('ASCII')
            ->chunk(3);

        $this->assertSame('foo', $this->get($splits, 0)->toString());
        $this->assertSame(
            \mb_substr('🙏', 0, 3, 'ASCII'),
            $this->get($splits, 1)->toString(),
        );
        $this->assertSame(
            \mb_substr('🙏', 3, 4, 'ASCII').'ba',
            $this->get($splits, 2)->toString(),
        );
        $this->assertSame('r', $this->get($splits, 3)->toString());
    }

    public function testPosition()
    {
        $str = S::of('foo');

        $this->assertSame(
            1,
            $str->position('o')->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            1,
            $str->position(S::of('o'))->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            2,
            $str->position('o', 2)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );

        $emoji = S::of('foo🙏bar');

        $this->assertSame(
            4,
            $emoji->position('bar')->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            7,
            $emoji->toEncoding('ASCII')->position('bar')->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenPositionNotFound()
    {
        $this->assertNull(
            S::of('bar')->position('o')->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReplace()
    {
        $str = S::of('<body text="%body%">');

        $str2 = $str->replace('%body%', 'black');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('<body text="black">', $str2->toString());
        $this->assertSame('<body text="%body%">', $str->toString());

        $this->assertSame('foo', S::of('foo')->replace('.', '/')->toString());
        $this->assertSame('foo/bar', S::of('foo.bar')->replace('.', '/')->toString());
        $this->assertSame(
            'foo/bar',
            S::of('foo.bar')
                ->replace(S::of('.'), S::of('/'))
                ->toString(),
        );
    }

    public function testReplaceWithDifferentEncoding()
    {
        $str = S::of('foo🙏🙏🙏bar');

        $str2 = $str->replace(
            \mb_substr('🙏', 0, 1, 'ASCII'),
            'baz',
        );
        $remaining = \mb_substr('🙏', 1, null, 'ASCII');
        $this->assertSame('foo🙏🙏🙏bar', $str->toString());
        $this->assertSame(
            'foobaz'.$remaining.'baz'.$remaining.'baz'.$remaining.'bar',
            $str2->toString(),
        );

        $str3 = $str->toEncoding('ASCII')->replace(
            \mb_substr('🙏', 0, 1, 'ASCII'),
            'baz',
        );
        $this->assertSame('foo🙏🙏🙏bar', $str->toString());
        $subPray = \mb_substr('🙏', 1, null, 'ASCII');
        $this->assertSame(
            'foobaz'.$subPray.'baz'.$subPray.'baz'.$subPray.'bar',
            $str3->toString(),
        );
    }

    public function testToUpper()
    {
        $str = S::of('foo🙏');

        $str2 = $str->toUpper();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('FOO🙏', $str2->toString());
        $this->assertSame('foo🙏', $str->toString());
        $this->assertSame('ÉGÉRIE', S::of('égérie')->toUpper()->toString());
    }

    public function testToLower()
    {
        $str = S::of('FOO🙏');

        $str2 = $str->toLower();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo🙏', $str2->toString());
        $this->assertSame('FOO🙏', $str->toString());
        $this->assertSame('égérie', S::of('ÉGÉRIE')->toLower()->toString());
    }

    public function testLength()
    {
        $this->assertSame(4, S::of('foo🙏')->length());
        $this->assertSame(7, S::of('foo🙏')->toEncoding('ASCII')->length());
    }

    public function testEmpty()
    {
        $this->assertTrue(S::of('')->empty());
        $this->assertFalse(S::of('🙏')->empty());
        $this->assertFalse(S::of('🙏', 'ASCII')->substring(0, 1)->empty());
    }

    public function testReverse()
    {
        $str = S::of('foo🙏');

        $str2 = $str->reverse();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('🙏oof', $str2->toString());
        $this->assertSame('foo🙏', $str->toString());
        $this->assertSame(
            \strrev('🙏').'oof',
            $str->toEncoding('ASCII')->reverse()->toString(),
        );
    }

    public function testReverseKeepTheGivenEncoding()
    {
        $this->assertSame(
            'UTF-8',
            S::of('foo')->reverse()->encoding()->toString(),
        );
        $this->assertSame(
            'ASCII',
            S::of('foo', 'ASCII')->reverse()->encoding()->toString(),
        );
    }

    public function testPad()
    {
        $str = S::of('foo');

        $str2 = $str->rightPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo000', $str2->toString());
        $this->assertSame('foo', $str->toString());
        $this->assertSame('foo000', $str->rightPad(6, S::of('0'))->toString());

        $str2 = $str->leftPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('000foo', $str2->toString());
        $this->assertSame('foo', $str->toString());
        $this->assertSame('000foo', $str->leftPad(6, S::of('0'))->toString());

        $str2 = $str->uniPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('0foo00', $str2->toString());
        $this->assertSame('foo', $str->toString());
        $this->assertSame('0foo00', $str->uniPad(6, S::of('0'))->toString());
    }

    public function testRepeat()
    {
        $str = S::of('foo');

        $str2 = $str->repeat(3);
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foofoofoo', $str2->toString());
        $this->assertSame('foo', $str->toString());
        $this->assertSame('🙏🙏', S::of('🙏')->repeat(2)->toString());
        $this->assertSame('🙏🙏', S::of('🙏')->toEncoding('ASCII')->repeat(2)->toString());
    }

    public function testStripSlashes()
    {
        $str = S::of("Is your name O\'reilly?");

        $str2 = $str->stripSlashes();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame("Is your name O\'reilly?", $str->toString());
        $this->assertSame("Is your name O'reilly?", $str2->toString());
    }

    public function testStripCSlahes()
    {
        $str = S::of('He\xallo');

        $str2 = $str->stripCSlashes();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('He\xallo', $str->toString());
        $this->assertSame('He' . "\n" . 'llo', $str2->toString());
    }

    public function testWordCount()
    {
        $str = S::of("Hello fri3nd, you're
                    looking          good today!");

        $this->assertSame(7, $str->wordCount());
        $this->assertSame(6, $str->wordCount('àáãç3'));
    }

    public function testWords()
    {
        $str = S::of("Hello fri3nd, you're
        looking          good today!");

        $map = $str->words();
        $this->assertInstanceOf(Map::class, $map);
        $words = [
            0 => 'Hello',
            6 => 'fri',
            10 => 'nd',
            14 => 'you\'re',
            29 => 'looking',
            46 => 'good',
            51 => 'today',
        ];

        foreach ($words as $pos => $word) {
            $this->assertInstanceOf(S::class, $this->get($map, $pos));
            $this->assertSame($word, $this->get($map, $pos)->toString());
        }

        $map = $str->words('àáãç3');
        $this->assertInstanceOf(Map::class, $map);
        $words = [
            0 => 'Hello',
            6 => 'fri3nd',
            14 => 'you\'re',
            29 => 'looking',
            46 => 'good',
            51 => 'today',
        ];

        foreach ($words as $pos => $word) {
            $this->assertInstanceOf(S::class, $this->get($map, $pos));
            $this->assertSame($word, $this->get($map, $pos)->toString());
        }
    }

    public function testPregSplit()
    {
        $str = S::of('hypertext language, programming');

        $c = $str->pregSplit('/[\s,]+/');
        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertSame('hypertext', $this->get($c, 0)->toString());
        $this->assertSame('language', $this->get($c, 1)->toString());
        $this->assertSame('programming', $this->get($c, 2)->toString());

        $c = $str->pregSplit(S::of('/[\s,]+/'));
        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertSame('hypertext', $this->get($c, 0)->toString());
        $this->assertSame('language', $this->get($c, 1)->toString());
        $this->assertSame('programming', $this->get($c, 2)->toString());
    }

    public function testMatches()
    {
        $str = S::of('abcdef');

        $this->assertFalse($str->matches('/^def/'));
        $this->assertTrue($str->matches('/^abc/'));

        $this->assertTrue(S::of('foo🙏bar')->matches('/🙏/'));
        $this->assertTrue(S::of('foo🙏bar')->toEncoding('ASCII')->matches('/🙏/'));
        $this->assertTrue(S::of('foo🙏bar')->matches(S::of('/🙏/')));
    }

    public function testCapture()
    {
        $str = S::of('http://www.php.net/index.html');

        $map = $str->capture('@^(?:http://)?(?P<host>[^/]+)@i');
        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame('http://www.php.net', $this->get($map, 0)->toString());
        $this->assertSame('www.php.net', $this->get($map, 1)->toString());
        $this->assertSame('www.php.net', $this->get($map, 'host')->toString());

        $map = $str->capture(S::of('@^(?:http://)?(?P<host>[^/]+)@i'));
        $this->assertCount(3, $map);
    }

    public function testCastNullValuesWhenCapturing()
    {
        $str = S::of('en;q=0.7');

        $matches = $str->capture('~(?<lang>([a-zA-Z0-9]+(-[a-zA-Z0-9]+)*|\*))(; ?q=(?<quality>\d+(\.\d+)?))?~');
        $this->assertInstanceOf(Map::class, $matches);
        $this->assertCount(9, $matches);
        $this->assertSame('en;q=0.7', $this->get($matches, 0)->toString());
        $this->assertSame('en', $this->get($matches, 1)->toString());
        $this->assertSame('en', $this->get($matches, 2)->toString());
        $this->assertSame('', $this->get($matches, 3)->toString());
        $this->assertSame('en', $this->get($matches, 'lang')->toString());
        $this->assertSame(';q=0.7', $this->get($matches, 4)->toString());
        $this->assertSame('0.7', $this->get($matches, 5)->toString());
        $this->assertSame('0.7', $this->get($matches, 'quality')->toString());
        $this->assertSame('.7', $this->get($matches, 6)->toString());
    }

    public function testPregReplace()
    {
        $str = S::of('April 15, 2003');

        $str2 = $str->pregReplace('/(\w+) (\d+), (\d+)/i', '${1}1,$3');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('April1,2003', $str2->toString());
        $this->assertSame('April 15, 2003', $str->toString());
        $this->assertSame(
            'April1,2003',
            $str
                ->pregReplace(S::of('/(\w+) (\d+), (\d+)/i'), S::of('${1}1,$3'))
                ->toString(),
        );
    }

    public function testSubstring()
    {
        $str = S::of('foobarbaz');

        $str2 = $str->substring(3);
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('barbaz', $str2->toString());
        $this->assertSame('foobarbaz', $str->toString());

        $str3 = $str->substring(3, 3);
        $this->assertInstanceOf(S::class, $str3);
        $this->assertNotSame($str, $str3);
        $this->assertSame('bar', $str3->toString());
        $this->assertSame('foobarbaz', $str->toString());

        $str4 = ($str = S::of(''))->substring(0, -1);

        $this->assertSame($str, $str4);
    }

    public function testTake()
    {
        $str = S::of('foobarbaz');

        $str2 = $str->take(3);

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', $str2->toString());
        $this->assertSame('foobarbaz', $str->toString());
    }

    public function testTakeEnd()
    {
        $str = S::of('foobarbaz');

        $str2 = $str->takeEnd(3);

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('baz', $str2->toString());
        $this->assertSame('foobarbaz', $str->toString());
    }

    public function testDrop()
    {
        $str = S::of('foobarbaz');

        $str2 = $str->drop(3);

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('barbaz', $str2->toString());
        $this->assertSame('foobarbaz', $str->toString());
    }

    public function testDropEnd()
    {
        $str = S::of('foobarbaz');

        $str2 = $str->dropEnd(3);

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foobar', $str2->toString());
        $this->assertSame('foobarbaz', $str->toString());
    }

    public function testSubstringUtf8ManipulatedAsAscii()
    {
        $str = S::of('foo🙏bar')->toEncoding('ASCII');

        $this->assertSame('🙏bar', $str->substring(3)->toString());
        $this->assertSame('🙏', $str->substring(3, 4)->toString());
        $this->assertSame(
            \mb_substr('🙏', 0, 1, 'ASCII'),
            $str->substring(3, 1)->toString(),
        );
    }

    public function testSprintf()
    {
        $str = S::of('foo %s baz');

        $str2 = $str->sprintf('bar');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo bar baz', $str2->toString());
        $this->assertSame('foo %s baz', $str->toString());
    }

    public function testUcfirst()
    {
        $str = S::of('foo');

        $str2 = $str->ucfirst();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', $str->toString());
        $this->assertSame('Foo', $str2->toString());
        $this->assertSame('🙏', S::of('🙏')->ucfirst()->toString());
        $this->assertSame('Égérie', S::of('égérie')->ucfirst()->toString());
    }

    public function testLcfirst()
    {
        $str = S::of('FOO');

        $str2 = $str->lcfirst();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('FOO', $str->toString());
        $this->assertSame('fOO', $str2->toString());
        $this->assertSame('🙏', S::of('🙏')->lcfirst()->toString());
        $this->assertSame('éGÉRIE', S::of('ÉGÉRIE')->lcfirst()->toString());
    }

    public function testCamelize()
    {
        $str = S::of('foo_bar baz');

        $str2 = $str->camelize();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo_bar baz', $str->toString());
        $this->assertSame('fooBarBaz', $str2->toString());
    }

    public function testAppend()
    {
        $str = S::of('foo');

        $str2 = $str->append(' bar');
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', $str->toString());
        $this->assertSame('foo bar', $str2->toString());
        $this->assertSame('foo bar', $str->append(S::of(' bar'))->toString());
    }

    public function testPrepend()
    {
        $str = S::of('foo');

        $str2 = $str->prepend('baz ');
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', $str->toString());
        $this->assertSame('baz foo', $str2->toString());
        $this->assertSame('baz foo', $str->prepend(S::of('baz '))->toString());
    }

    public function testEquals()
    {
        $this->assertTrue(S::of('foo')->equals(S::of('foo')));
        $this->assertFalse(S::of('foo')->equals(S::of('fo')));
    }

    public function testTrim()
    {
        $str = S::of(' foo ');
        $str2 = $str->trim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', $str->toString());
        $this->assertSame('foo', $str2->toString());
        $this->assertSame('f', $str2->trim('o')->toString());
    }

    public function testRightTrim()
    {
        $str = S::of(' foo ');
        $str2 = $str->rightTrim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', $str->toString());
        $this->assertSame(' foo', $str2->toString());
        $this->assertSame(' f', $str2->rightTrim('o')->toString());
    }

    public function testLeftTrim()
    {
        $str = S::of(' foo ');
        $str2 = $str->leftTrim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', $str->toString());
        $this->assertSame('foo ', $str2->toString());
        $this->assertSame('oo ', $str2->leftTrim('f')->toString());
    }

    public function testContains()
    {
        $str = S::of('foobar');

        $this->assertTrue($str->contains('foo'));
        $this->assertTrue($str->contains('bar'));
        $this->assertFalse($str->contains('baz'));
        $this->assertTrue($str->contains(S::of('foo')));
        $this->assertFalse($str->contains(S::of('baz')));
    }

    public function testStartsWith()
    {
        $str = S::of('foobar');

        $this->assertTrue($str->startsWith(''));
        $this->assertTrue($str->startsWith('foo'));
        $this->assertTrue($str->startsWith('foob'));
        $this->assertTrue($str->startsWith('foobar'));
        $this->assertFalse($str->startsWith('bar'));
        $this->assertFalse($str->startsWith('oobar'));
        $this->assertFalse($str->startsWith('foobar '));
        $this->assertTrue($str->startsWith(S::of('foobar')));
        $this->assertFalse($str->startsWith(S::of('bar')));
    }

    public function testEndsWith()
    {
        $str = S::of('foobar');

        $this->assertTrue($str->endsWith(''));
        $this->assertTrue($str->endsWith('bar'));
        $this->assertTrue($str->endsWith('obar'));
        $this->assertTrue($str->endsWith('foobar'));
        $this->assertFalse($str->endsWith('foo'));
        $this->assertFalse($str->endsWith('fooba'));
        $this->assertFalse($str->endsWith('xfoobar'));
        $this->assertTrue($str->endsWith(S::of('foobar')));
        $this->assertFalse($str->endsWith(S::of('foo')));
    }

    public function testPregQuote()
    {
        $a = S::of('foo#bar.*');
        $b = $a->pregQuote();
        $c = $a->pregQuote('o');

        $this->assertInstanceOf(S::class, $b);
        $this->assertInstanceOf(S::class, $c);
        $this->assertSame('foo#bar.*', $a->toString());
        $this->assertSame('foo\#bar\.\*', $b->toString());
        $this->assertSame('f\o\o\#bar\.\*', $c->toString());
    }

    public function testMap()
    {
        $a = S::of('foo');
        $b = $a->map(function($string, $encoding) use ($a) {
            $this->assertSame('foo', $string);
            $this->assertSame($a->encoding()->toString(), $encoding);

            return 'bar';
        });

        $this->assertNotSame($a, $b);
        $this->assertSame('foo', $a->toString());
        $this->assertSame('bar', $b->toString());
    }

    public function testFlatMap()
    {
        $expected = S::of('bar');
        $a = S::of('foo');
        $b = $a->flatMap(function($string, $encoding) use ($a, $expected) {
            $this->assertSame('foo', $string);
            $this->assertSame($a->encoding()->toString(), $encoding);

            return $expected;
        });

        $this->assertNotSame($a, $b);
        $this->assertSame($expected, $b);
        $this->assertSame('foo', $a->toString());
        $this->assertSame('bar', $b->toString());
    }

    public function testJoinSet()
    {
        $str = S::of('|')->join(Set::of('1', '2', '3'));

        $this->assertInstanceOf(S::class, $str);
        $this->assertSame('1|2|3', $str->toString());

        $str = S::of('|')->join(Set::of('1', '2', '3')->map(S::of(...)));

        $this->assertInstanceOf(S::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testJoinSequence()
    {
        $str = S::of('|')->join(Sequence::of('1', '2', '3'));

        $this->assertInstanceOf(S::class, $str);
        $this->assertSame('1|2|3', $str->toString());

        $str = S::of('|')->join(Sequence::of('1', '2', '3')->map(S::of(...)));

        $this->assertInstanceOf(S::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testMaybe()
    {
        $str = S::of('foobar');

        $this->assertSame(
            $str,
            $str
                ->maybe(static fn($str) => $str->startsWith('foo'))
                ->match(
                    static fn($str) => $str,
                    static fn() => null,
                ),
        );
        $this->assertNull(
            $str
                ->maybe(static fn($str) => $str->startsWith('bar'))
                ->match(
                    static fn($str) => $str,
                    static fn() => null,
                ),
        );
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
