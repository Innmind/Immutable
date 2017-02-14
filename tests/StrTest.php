<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Str as S,
    PrimitiveInterface,
    StringableInterface,
    StreamInterface,
    MapInterface
};

class StrTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $str = new S('foo');

        $this->assertInstanceOf(PrimitiveInterface::class, $str);
        $this->assertInstanceOf(StringableInterface::class, $str);
        $this->assertSame('foo', $str->toPrimitive());
        $this->assertSame('foo', (string) $str);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage must be of the type string, integer given
     */
    public function testThrowWhenInvalidType()
    {
        new S(42);
    }

    public function testSplit()
    {
        $str = new S('foo');

        $stream = $str->split();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(S::class, (string) $stream->type());
        $this->assertCount(3, $stream);

        foreach ($stream as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', (string) $stream[0]);
        $this->assertSame('o', (string) $stream[1]);
        $this->assertSame('o', (string) $stream[2]);

        $stream = $str->split('');
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(S::class, (string) $stream->type());
        $this->assertCount(3, $stream);

        foreach ($stream as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', (string) $stream[0]);
        $this->assertSame('o', (string) $stream[1]);
        $this->assertSame('o', (string) $stream[2]);

        $str = new S('f|o|o');
        $stream = $str->split('|');
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(S::class, (string) $stream->type());
        $this->assertCount(3, $stream);

        foreach ($stream as $part) {
            $this->assertInstanceOf(S::class, $part);
        }

        $this->assertSame('f', (string) $stream[0]);
        $this->assertSame('o', (string) $stream[1]);
        $this->assertSame('o', (string) $stream[2]);
    }

    public function testChunk()
    {
        $str = new S('foobarbaz');

        $stream = $str->chunk(4);
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(S::class, (string) $stream->type());
        $this->assertInstanceOf(S::class, $stream[0]);
        $this->assertInstanceOf(S::class, $stream[1]);
        $this->assertInstanceOf(S::class, $stream[2]);
        $this->assertSame('foob', (string) $stream[0]);
        $this->assertSame('arba', (string) $stream[1]);
        $this->assertSame('z', (string) $stream[2]);
    }

    public function testPosition()
    {
        $str = new S('foo');

        $this->assertSame(1, $str->position('o'));
        $this->assertSame(2, $str->position('o', 2));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\SubstringException
     * @expectedExceptionMessage Substring "o" not found
     */
    public function testThrowWhenPositionNotFound()
    {
        (new S('bar'))->position('o');
    }

    public function testReplace()
    {
        $str = new s('<body text="%body%">');

        $str2 = $str->replace('%body%', 'black');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('<body text="black">', (string) $str2);
        $this->assertSame('<body text="%body%">', (string) $str);
    }

    public function testStr()
    {
        $str = new S('name@example.com');

        $str2 = $str->str('@');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('@example.com', (string) $str2);
        $this->assertSame('name@example.com', (string) $str);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\SubstringException
     * @expectedExceptionMessage Substring "foo" not found
     */
    public function testThrowWhenStrDelimiterNotFound()
    {
        (new S('name@example.com'))->str('foo');
    }

    public function testToUpper()
    {
        $str = new S('foo');

        $str2 = $str->toUpper();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('FOO', (string) $str2);
        $this->assertSame('foo', (string) $str);
    }

    public function testToLower()
    {
        $str = new S('FOO');

        $str2 = $str->toLower();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', (string) $str2);
        $this->assertSame('FOO', (string) $str);
    }

    public function testLength()
    {
        $this->assertSame(3, (new S('foo'))->length());
    }

    public function testReverse()
    {
        $str = new S('foo');

        $str2 = $str->reverse();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('oof', (string) $str2);
        $this->assertSame('foo', (string) $str);
    }

    public function testPad()
    {
        $str = new S('foo');

        $str2 = $str->rightPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo000', (string) $str2);
        $this->assertSame('foo', (string) $str);

        $str2 = $str->leftPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('000foo', (string) $str2);
        $this->assertSame('foo', (string) $str);

        $str2 = $str->uniPad(6, '0');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('0foo00', (string) $str2);
        $this->assertSame('foo', (string) $str);
    }

    public function testCspn()
    {
        $str = new S('abcdhelloabcd');

        $this->assertSame(0, $str->cspn('abcd'));
        $this->assertSame(5, $str->cspn('abcd', -9));
        $this->assertSame(4, $str->cspn('abcd', -9, -5));
    }

    public function testRepeat()
    {
        $str = new s('foo');

        $str2 = $str->repeat(3);
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foofoofoo', (string) $str2);
        $this->assertSame('foo', (string) $str);
    }

    public function testShuffle()
    {
        $str = new S('shuffle');

        $str2 = $str->shuffle();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('shuffle', (string) $str);
        $this->assertSame(7, $str2->length());

        try {
            foreach ($str2->split() as $char) {
                $str->position((string) $char);
            }
        } catch (\Exception $e) {
            $this->fail('every character should be in the original string');
        }
    }

    public function testStripSlashes()
    {
        $str = new S("Is your name O\'reilly?");

        $str2 = $str->stripSlashes();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame("Is your name O\'reilly?", (string) $str);
        $this->assertSame("Is your name O'reilly?", (string) $str2);
    }

    public function testStripCSlahes()
    {
        $str = new S('He\xallo');

        $str2 = $str->stripCSlashes();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('He\xallo', (string) $str);
        $this->assertSame('He' . "\n" . 'llo', (string) $str2);
    }

    public function testWordCount()
    {
        $str = new S("Hello fri3nd, you're
                    looking          good today!");

        $this->assertSame(7, $str->wordCount());
        $this->assertSame(6, $str->wordCount('àáãç3'));
    }

    public function testWords()
    {
        $str = new S("Hello fri3nd, you're
        looking          good today!");

        $map = $str->words();
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('int', (string) $map->keyType());
        $this->assertSame(S::class, (string) $map->valueType());
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
            $this->assertInstanceOf(S::class, $map[$pos]);
            $this->assertSame($word, (string) $map[$pos]);
        }

        $map = $str->words('àáãç3');
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('int', (string) $map->keyType());
        $this->assertSame(S::class, (string) $map->valueType());
        $words = [
            0 => 'Hello',
            6 => 'fri3nd',
            14 => 'you\'re',
            29 => 'looking',
            46 => 'good',
            51 => 'today',
        ];

        foreach ($words as $pos => $word) {
            $this->assertInstanceOf(S::class, $map[$pos]);
            $this->assertSame($word, (string) $map[$pos]);
        }
    }

    public function testPregSplit()
    {
        $str = new S('hypertext language, programming');

        $c = $str->pregSplit('/[\s,]+/');
        $this->assertInstanceOf(StreamInterface::class, $c);
        $this->assertSame(S::class, (string) $c->type());
        $this->assertSame('hypertext', (string) $c[0]);
        $this->assertSame('language', (string) $c[1]);
        $this->assertSame('programming', (string) $c[2]);
    }

    public function testMatches()
    {
        $str = new S('abcdef');

        $this->assertFalse($str->matches('/^def/'));
        $this->assertTrue($str->matches('/^abc/'));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\RegexException
     * @expectedExceptionMessage Internal error
     */
    public function testThrowWhenMatchInvalidRegex()
    {
        (new S(''))->matches('/foo/', 4);
    }

    public function testCapture()
    {
        $str = new S('http://www.php.net/index.html');

        $map = $str->capture('@^(?:http://)?(?P<host>[^/]+)@i');
        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('scalar', (string) $map->keyType());
        $this->assertSame(S::class, (string) $map->valueType());
        $this->assertCount(3, $map);
        $this->assertSame('http://www.php.net', (string) $map[0]);
        $this->assertSame('www.php.net', (string) $map[1]);
        $this->assertSame('www.php.net', (string) $map['host']);
    }

    public function testCastNullValuesWhenCapturing()
    {
        $str = new S('en;q=0.7');

        $matches = $str->capture('~(?<lang>([a-zA-Z0-9]+(-[a-zA-Z0-9]+)*|\*))(; ?q=(?<quality>\d+(\.\d+)?))?~');
        $this->assertInstanceOf(MapInterface::class, $matches);
        $this->assertSame('scalar', (string) $matches->keyType());
        $this->assertSame(S::class, (string) $matches->valueType());
        $this->assertCount(9, $matches);
        $this->assertSame('en;q=0.7', (string) $matches->get(0));
        $this->assertSame('en', (string) $matches->get(1));
        $this->assertSame('en', (string) $matches->get(2));
        $this->assertSame('', (string) $matches->get(3));
        $this->assertSame('en', (string) $matches->get('lang'));
        $this->assertSame(';q=0.7', (string) $matches->get(4));
        $this->assertSame('0.7', (string) $matches->get(5));
        $this->assertSame('0.7', (string) $matches->get('quality'));
        $this->assertSame('.7', (string) $matches->get(6));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\RegexException
     * @expectedExceptionMessage Internal error
     */
    public function testThrowWhenGettingMatchesInvalidRegex()
    {
        (new S(''))->getMatches('/foo/', 4);
    }

    public function testPregReplace()
    {
        $str = new S('April 15, 2003');

        $str2 = $str->pregReplace('/(\w+) (\d+), (\d+)/i', '${1}1,$3');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('April1,2003', (string) $str2);
        $this->assertSame('April 15, 2003', (string) $str);
    }

    public function testSubstring()
    {
        $str = new S('foobarbaz');

        $str2 = $str->substring(3);
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('barbaz', (string) $str2);
        $this->assertSame('foobarbaz', (string) $str);

        $str3 = $str->substring(3, 3);
        $this->assertInstanceOf(S::class, $str3);
        $this->assertNotSame($str, $str3);
        $this->assertSame('bar', (string) $str3);
        $this->assertSame('foobarbaz', (string) $str);
    }

    public function testSprintf()
    {
        $str = new S('foo %s baz');

        $str2 = $str->sprintf('bar');
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo bar baz', (string) $str2);
        $this->assertSame('foo %s baz', (string) $str);
    }

    public function testUcfirst()
    {
        $str = new S('foo');

        $str2 = $str->ucfirst();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', (string) $str);
        $this->assertSame('Foo', (string) $str2);
    }

    public function testLcfirst()
    {
        $str = new S('FOO');

        $str2 = $str->lcfirst();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('FOO', (string) $str);
        $this->assertSame('fOO', (string) $str2);
    }

    public function testCamelize()
    {
        $str = new S('foo_bar baz');

        $str2 = $str->camelize();
        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo_bar baz', (string) $str);
        $this->assertSame('FooBarBaz', (string) $str2);
    }

    public function testAppend()
    {
        $str = new S('foo');

        $str2 = $str->append(' bar');
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', (string) $str);
        $this->assertSame('foo bar', (string) $str2);
    }

    public function testPrepend()
    {
        $str = new S('foo');

        $str2 = $str->prepend('baz ');
        $this->assertNotSame($str, $str2);
        $this->assertSame('foo', (string) $str);
        $this->assertSame('baz foo', (string) $str2);
    }

    public function testEquals()
    {
        $this->assertTrue((new S('foo'))->equals(new S('foo')));
        $this->assertFalse((new S('foo'))->equals(new S('fo')));
    }

    public function testTrim()
    {
        $str = new S(' foo ');
        $str2 = $str->trim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', (string) $str);
        $this->assertSame('foo', (string) $str2);
        $this->assertSame('f', (string) $str2->trim('o'));
    }

    public function testRightTrim()
    {
        $str = new S(' foo ');
        $str2 = $str->rightTrim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', (string) $str);
        $this->assertSame(' foo', (string) $str2);
        $this->assertSame(' f', (string) $str2->rightTrim('o'));
    }

    public function testLeftTrim()
    {
        $str = new S(' foo ');
        $str2 = $str->leftTrim();

        $this->assertInstanceOf(S::class, $str2);
        $this->assertNotSame($str, $str2);
        $this->assertSame(' foo ', (string) $str);
        $this->assertSame('foo ', (string) $str2);
        $this->assertSame('oo ', (string) $str2->leftTrim('f'));
    }

    public function testContains()
    {
        $str = new S('foobar');

        $this->assertTrue($str->contains('foo'));
        $this->assertTrue($str->contains('bar'));
        $this->assertFalse($str->contains('baz'));
    }

    public function testPregQuote()
    {
        $a = new S('foo#bar.*');
        $b = $a->pregQuote();
        $c = $a->pregQuote('#');

        $this->assertInstanceOf(S::class, $b);
        $this->assertInstanceOf(S::class, $c);
        $this->assertSame('foo#bar.*', (string) $a);
        $this->assertSame('foo#bar\.\*', (string) $b);
        $this->assertSame('foo\#bar\.\*', (string) $c);
    }
}
