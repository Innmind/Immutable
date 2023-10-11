<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

final class RegisterCleanup
{
    /** @var callable(): void */
    private $cleanup;
    private ?self $child = null;

    /**
     * @psalm-mutation-free
     *
     * @param callable(): void $cleanup
     */
    private function __construct(callable $cleanup)
    {
        $this->cleanup = $cleanup;
    }

    /**
     * @param callable(): void $cleanup
     */
    public function __invoke(callable $cleanup): void
    {
        $this->cleanup = $cleanup;
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function noop(): self
    {
        return new self(static fn() => null);
    }

    /**
     * @internal
     */
    public function push(): self
    {
        return $this->child = self::noop();
    }

    /**
     * @internal
     */
    public function pop(): void
    {
        $this->child = null;
    }

    /**
     * @internal
     */
    public function cleanup(): void
    {
        if ($this->child) {
            $this->child->cleanup();
        }

        ($this->cleanup)();
    }
}
