<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\StringPrimitive as S;
use Innmind\Immutable\PrimitiveInterface;
use Innmind\Immutable\StringableInterface;
use Innmind\Immutable\TypedCollectionInterface;
use Innmind\Immutable\CollectionInterface;

class StringPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $s = new S('foo');

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertInstanceOf(StringableInterface::class, $s);
        $this->assertSame('foo', $s->toPrimitive());
        $this->assertSame('foo', (string) $s);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\TypeException
     * @expectedExceptionMessage Value must be a string
     */
    public function testThrowWhenInvalidType()
    {
        new S(42);
    }

    public function testSplit()
    {
        $s = new S('foo');

        $c = $s->split();
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $this->assertSame(3, $c->count());
        foreach ($c as $part) {
            $this->assertInstanceOf(S::class, $part);
        }
        $this->assertSame('f', (string) $c[0]);
        $this->assertSame('o', (string) $c[1]);
        $this->assertSame('o', (string) $c[2]);

        $c = $s->split('');
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $this->assertSame(3, $c->count());
        foreach ($c as $part) {
            $this->assertInstanceOf(S::class, $part);
        }
        $this->assertSame('f', (string) $c[0]);
        $this->assertSame('o', (string) $c[1]);
        $this->assertSame('o', (string) $c[2]);

        $s = new S('f|o|o');
        $c = $s->split('|');
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $this->assertSame(3, $c->count());
        foreach ($c as $part) {
            $this->assertInstanceOf(S::class, $part);
        }
        $this->assertSame('f', (string) $c[0]);
        $this->assertSame('o', (string) $c[1]);
        $this->assertSame('o', (string) $c[2]);
    }

    public function testChunk()
    {
        $s = new S('foobarbaz');

        $c = $s->chunk(4);
        $this->assertInstanceOf(CollectionInterface::class, $c);
        $this->assertInstanceOf(TypedCollectionInterface::class, $c[0]);
        $this->assertInstanceOf(TypedCollectionInterface::class, $c[1]);
        $this->assertInstanceOf(TypedCollectionInterface::class, $c[2]);
        $this->assertSame(S::class, $c[0]->getType());
        $this->assertSame(S::class, $c[1]->getType());
        $this->assertSame(S::class, $c[2]->getType());
        $this->assertSame('f', $c[0][0]->toPrimitive());
        $this->assertSame('o', $c[0][1]->toPrimitive());
        $this->assertSame('o', $c[0][2]->toPrimitive());
        $this->assertSame('b', $c[0][3]->toPrimitive());
        $this->assertSame('a', $c[1][0]->toPrimitive());
        $this->assertSame('r', $c[1][1]->toPrimitive());
        $this->assertSame('b', $c[1][2]->toPrimitive());
        $this->assertSame('a', $c[1][3]->toPrimitive());
        $this->assertSame('z', $c[2][0]->toPrimitive());
    }

    public function testPos()
    {
        $s = new S('foo');

        $this->assertSame(1, $s->pos('o'));
        $this->assertSame(2, $s->pos('o', 2));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\SubstringException
     * @expectedExceptionMessage Substring "o" not found
     */
    public function testThrowWhenPositionNotFound()
    {
        (new S('bar'))->pos('o');
    }

    public function testReplace()
    {
        $s = new s('<body text="%body%">');

        $s2 = $s->replace('%body%', 'black');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('<body text="black">', (string) $s2);
        $this->assertSame('<body text="%body%">', (string) $s);
    }

    public function testStr()
    {
        $s = new S('name@example.com');

        $s2 = $s->str('@');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('@example.com', (string) $s2);
        $this->assertSame('name@example.com', (string) $s);
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
        $s = new S('foo');

        $s2 = $s->toUpper();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('FOO', (string) $s2);
        $this->assertSame('foo', (string) $s);
    }

    public function testToLower()
    {
        $s = new S('FOO');

        $s2 = $s->toLower();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('foo', (string) $s2);
        $this->assertSame('FOO', (string) $s);
    }

    public function testLength()
    {
        $this->assertSame(3, (new S('foo'))->length());
    }

    public function testReverse()
    {
        $s = new S('foo');

        $s2 = $s->reverse();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('oof', (string) $s2);
        $this->assertSame('foo', (string) $s);
    }

    public function testPad()
    {
        $s = new S('foo');

        $s2 = $s->pad(6);
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('foo   ', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->pad(6, '0');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('foo000', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->pad(6, '0', STR_PAD_LEFT);
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('000foo', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->pad(6, '0', STR_PAD_BOTH);
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('0foo00', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->rightPad(6, '0');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('foo000', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->leftPad(6, '0');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('000foo', (string) $s2);
        $this->assertSame('foo', (string) $s);

        $s2 = $s->uniPad(6, '0');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('0foo00', (string) $s2);
        $this->assertSame('foo', (string) $s);
    }

    public function testCspn()
    {
        $s = new S('abcdhelloabcd');

        $this->assertSame(0, $s->cspn('abcd'));
        $this->assertSame(5, $s->cspn('abcd', -9));
        $this->assertSame(4, $s->cspn('abcd', -9, -5));
    }

    public function testRepeat()
    {
        $s = new s('foo');

        $s2 = $s->repeat(3);
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('foofoofoo', (string) $s2);
        $this->assertSame('foo', (string) $s);
    }

    public function testShuffle()
    {
        $s = new S('shuffle');

        $s2 = $s->shuffle();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('shuffle', (string) $s);
        $this->assertSame(7, $s2->length());

        try {
            foreach ($s2->split() as $char) {
                $s->pos($char);
            }
        } catch (\Exception $e) {
            $this->fail('every character should be in the original string');
        }
    }

    public function testStripSlashes()
    {
        $s = new S("Is your name O\'reilly?");

        $s2 = $s->stripSlashes();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame("Is your name O\'reilly?", (string) $s);
        $this->assertSame("Is your name O'reilly?", (string) $s2);
    }

    public function testStripCSlahes()
    {
        $s = new S('He\xallo');

        $s2 = $s->stripCSlashes();
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('He\xallo', (string) $s);
        $this->assertSame('He' . "\n" . 'llo', (string) $s2);
    }

    public function testWordCount()
    {
        $s = new S("Hello fri3nd, you're
                    looking          good today!");

        $this->assertSame(7, $s->wordCount());
        $this->assertSame(6, $s->wordCount('àáãç3'));
    }

    public function testWords()
    {
        $s = new S("Hello fri3nd, you're
        looking          good today!");

        $c = $s->words();
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
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
            $this->assertInstanceOf(S::class, $c[$pos]);
            $this->assertSame($word, (string) $c[$pos]);
        }

        $c = $s->words('àáãç3');
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $words = [
            0 => 'Hello',
            6 => 'fri3nd',
            14 => 'you\'re',
            29 => 'looking',
            46 => 'good',
            51 => 'today',
        ];

        foreach ($words as $pos => $word) {
            $this->assertInstanceOf(S::class, $c[$pos]);
            $this->assertSame($word, (string) $c[$pos]);
        }
    }

    public function testPregSplit()
    {
        $s = new S('hypertext language, programming');

        $c = $s->pregSplit('/[\s,]+/');
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $this->assertSame('hypertext', (string) $c[0]);
        $this->assertSame('language', (string) $c[1]);
        $this->assertSame('programming', (string) $c[2]);
    }

    public function testMatch()
    {
        $s = new S('abcdef');

        $this->assertFalse($s->match('/^def/'));
        $this->assertTrue($s->match('/^abc/'));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\RegexException
     * @expectedExceptionMessage Internal error
     */
    public function testThrowWhenMatchInvalidRegex()
    {
        (new S(''))->match('/foo/', 0, 4);
    }

    public function testGetMatches()
    {
        $s = new S('http://www.php.net/index.html');

        $c = $s->getMatches('@^(?:http://)?([^/]+)@i');
        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame(S::class, $c->getType());
        $this->assertSame(2, $c->count());
        $this->assertSame('http://www.php.net', (string) $c[0]);
        $this->assertSame('www.php.net', (string) $c[1]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\RegexException
     * @expectedExceptionMessage Internal error
     */
    public function testThrowWhenGettingMatchesInvalidRegex()
    {
        (new S(''))->getMatches('/foo/', 0, 4);
    }

    public function testPregReplace()
    {
        $s = new S('April 15, 2003');

        $s2 = $s->pregReplace('/(\w+) (\d+), (\d+)/i', '${1}1,$3');
        $this->assertInstanceOf(S::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame('April1,2003', (string) $s2);
        $this->assertSame('April 15, 2003', (string) $s);
    }
}
