<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\ObjectStorage;

class ObjectStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testPrimitive()
    {
        $s = new ObjectStorage($spl = new \SplObjectStorage);

        $primitive = $s->toPrimitive();
        $this->assertInstanceOf(\SplObjectStorage::class, $primitive);
        $this->assertNotSame($spl, $primitive);
        $primitive->attach(new \stdClass);
        $this->assertSame(0, $spl->count());
    }

    public function testMerge()
    {
        $spl1 = new \SplObjectStorage;
        $spl2 = new \SplObjectStorage;
        $spl1->attach(new \stdClass);
        $spl2->attach(new \stdClass);
        $s = new ObjectStorage($spl1);
        $s2 = new ObjectStorage($spl2);

        $s3 = $s->merge($s2);
        $this->assertInstanceOf(ObjectStorage::class, $s3);
        $this->assertNotSame($s, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertSame(2, $s3->count());
    }

    public function testAttach()
    {
        $s = new ObjectStorage;

        $s2 = $s->attach(new \stdClass);
        $this->assertInstanceOf(ObjectStorage::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame(0, $s->count());
        $this->assertSame(1, $s2->count());

        $s2 = $s->attach($o = new \stdClass, 'foo');
        $this->assertSame('foo', $s2->toPrimitive()[$o]);
    }

    public function testContains()
    {
        $s = (new ObjectStorage)->attach($o = new \stdClass);

        $this->assertTrue($s->contains($o));
        $this->assertFalse($s->contains(new \stdClass));
    }

    public function testDetach()
    {
        $s = (new ObjectStorage)->attach($o = new \stdClass);

        $s2 = $s->detach($o);

        $this->assertInstanceOf(ObjectStorage::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertTrue($s->contains($o));
        $this->assertFalse($s2->contains($o));
    }

    public function testGetHash()
    {
        $s = (new ObjectStorage)->attach($o = new \stdClass);

        $this->assertTrue(is_string($s->getHash($o)));
        $this->assertSame(
            $s->toPrimitive()->getHash($o),
            $s->getHash($o)
        );
    }

    public function testGetInfo()
    {
        $s = (new ObjectStorage)->attach(new \stdClass);
        $this->assertSame(null, $s->getInfo());

        $s = (new ObjectStorage)->attach(new \stdClass, 42);
        $this->assertSame(42, $s->getInfo());
    }

    public function testRemoveAll()
    {
        $s = (new ObjectStorage)
            ->attach(new \stdClass)
            ->attach($o = new \stdClass);
        $s2 = (new ObjectStorage)->attach($o);

        $s3 = $s->removeAll($s2);

        $this->assertInstanceOf(ObjectStorage::class, $s3);
        $this->assertNotSame($s, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertSame(2, $s->count());
        $this->assertSame(1, $s2->count());
        $this->assertSame(1, $s3->count());
        $this->assertTrue($s->contains($o));
        $this->assertTrue($s2->contains($o));
        $this->assertFalse($s3->contains($o));
    }

    public function testRemoveAllExcept()
    {
        $s = (new ObjectStorage)
            ->attach(new \stdClass)
            ->attach($o = new \stdClass);
        $s2 = (new ObjectStorage)->attach($o);

        $s3 = $s->removeAllExcept($s2);

        $this->assertInstanceOf(ObjectStorage::class, $s3);
        $this->assertNotSame($s, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertSame(2, $s->count());
        $this->assertSame(1, $s2->count());
        $this->assertSame(1, $s3->count());
        $this->assertTrue($s->contains($o));
        $this->assertTrue($s2->contains($o));
        $this->assertTrue($s3->contains($o));
    }

    public function testSetInfo()
    {
        $s = (new ObjectStorage)
            ->attach($o = new \stdClass)
            ->attach($o2 = new \stdClass);
        $s->next();

        $s2 = $s->setInfo(42);
        $this->assertInstanceOf(ObjectStorage::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame(null, $s->toPrimitive()[$o]);
        $this->assertSame(null, $s->toPrimitive()[$o2]);
        $this->assertSame(null, $s2->toPrimitive()[$o]);
        $this->assertSame(42, $s2->toPrimitive()[$o2]);
    }

    public function testCount()
    {
        $s = (new ObjectStorage)->attach(new \stdClass);

        $this->assertSame(1, count($s));
    }

    public function testIterator()
    {
        $s = (new ObjectStorage)
            ->attach($o = new \stdClass)
            ->attach($o2 = new \stdClass)
            ->attach($o3 = new \stdClass);

        $this->assertInstanceOf(\Iterator::class, $s);
        $this->assertSame($o, $s->current());
        $this->assertSame(0, $s->key());
        $this->assertTrue($s->valid());
        $this->assertSame(null, $s->next());
        $this->assertSame($o2, $s->current());
        $this->assertSame(1, $s->key());
        $this->assertTrue($s->valid());
        $this->assertSame(null, $s->next());
        $this->assertSame($o3, $s->current());
        $this->assertSame(2, $s->key());
        $this->assertTrue($s->valid());
        $s->next();
        $this->assertFalse($s->valid());
        $this->assertSame(null, $s->rewind());
        $this->assertSame($o, $s->current());
        $this->assertSame(0, $s->key());
    }

    public function testArrayAccess()
    {
        $s = (new ObjectStorage)->attach($o = new \stdClass, 42);

        $this->assertInstanceOf(\ArrayAccess::class, $s);
        $this->assertTrue(isset($s[$o]));
        $this->assertSame(42, $s[$o]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify an immutable object storage
     */
    public function testThrowWhenTryingToSetAnObject()
    {
        $s = new ObjectStorage;

        $s[new \stdClass] = 42;
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify an immutable object storage
     */
    public function testThrowWhenTryingToUnsetAnObject()
    {
        $s = (new ObjectStorage)->attach($o = new \stdClass);

        unset($s[$o]);
    }

    public function testSerializable()
    {
        $s = (new ObjectStorage)->attach(new \stdClass);

        $this->assertInstanceOf(\Serializable::class, $s);
        $this->assertTrue(is_string($s->serialize()));
        $this->assertSame(
            'x:i:1;O:8:"stdClass":0:{},N;;m:a:0:{}',
            $s->serialize()
        );

        $s2 = (new ObjectStorage);
        $s3 = $s2->unserialize($s->serialize());

        $this->assertInstanceOf(ObjectStorage::class, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertSame(0, $s2->count());
        $this->assertSame(1, $s3->count());
    }

    public function testFilter()
    {
        $s = (new ObjectStorage)
            ->attach($o = new \stdClass, 'foo')
            ->attach(new \stdClass);

        $s2 = $s->filter(function ($object, $data) {
            return $data !== null;
        });

        $this->assertInstanceOf(ObjectStorage::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame(1, $s2->count());
        $this->assertSame(2, $s->count());
        $this->assertSame($o, $s2->current());
        $this->assertSame($o, $s->current());
    }

    public function testEach()
    {
        $s = (new ObjectStorage)
            ->attach($o = new \stdClass, 'foo')
            ->attach(new \stdClass, 'bar');
        $count = 0;

        $s2 = $s->each(function ($object, $data) use (&$count) {
            $this->assertInstanceOf('stdClass', $object);
            $this->assertTrue(is_string($data));
            ++$count;
        });

        $this->assertSame($s, $s2);
        $this->assertSame(2, $s->count());
        $this->assertSame($o, $s->current());
        $this->assertSame(2, $count);
    }

    public function testMap()
    {
        $s = (new ObjectStorage)
            ->attach($o = new \stdClass, 'foo')
            ->attach($o2 = new \stdClass, 'bar');

        $s2 = $s->map(function ($object, $data) {
            $o = new \stdClass;
            $o->$data = $object;

            return $o;
        });

        $this->assertInstanceOf(ObjectStorage::class, $s2);
        $this->assertNotSame($s, $s2);
        $this->assertSame(2, $s2->count());
        $this->assertInstanceOf('stdClass', $s2->current());
        $this->assertSame($o, $s2->current()->foo);
        $s2->next();
        $this->assertInstanceOf('stdClass', $s2->current());
        $this->assertSame($o2, $s2->current()->bar);
        $this->assertSame($o, $s->current());
    }
}
