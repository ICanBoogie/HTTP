<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\HTTP;

use ICanBoogie\Accessor\AccessorTrait;

/**
 * Used to define a dispatcher and its weight.
 *
 * ```php
 * <?php
 *
 * $dispatcher['my'] = new WeightedDispatcher('callback', 'before:that_other_dispatcher');
 * ```
 *
 * @property-read string|Dispatcher $dispatcher
 * @property-read int|string $weight
 */
class WeightedDispatcher
{
    /**
     * @uses get_dispatcher
     * @uses get_weight
     */
    use AccessorTrait;

    public const WEIGHT_TOP = 'top';
    public const WEIGHT_BOTTOM = 'bottom';
    public const WEIGHT_BEFORE_PREFIX = 'before:';
    public const WEIGHT_AFTER_PREFIX = 'after:';
    public const WEIGHT_DEFAULT = 0;

    protected function get_dispatcher(): Dispatcher|string
    {
        return $this->dispatcher;
    }

    protected function get_weight(): int|string
    {
        return $this->weight;
    }

    /**
     * Initializes the {@link $dispatcher} and {@link $weight} properties.
     *
     * @param string|Dispatcher $dispatcher
     * @param int|string $weight
     */
    public function __construct(
        private Dispatcher|string $dispatcher,
        private int|string $weight = self::WEIGHT_DEFAULT
    ) {
    }
}
