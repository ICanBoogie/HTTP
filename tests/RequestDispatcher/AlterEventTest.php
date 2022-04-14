<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\HTTP\RequestDispatcher;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\HTTP\RequestDispatcher\AlterEvent;
use PHPUnit\Framework\TestCase;

class AlterEventTest extends TestCase
{
    public function test_instance()
    {
        $dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $other_dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $dispatcher RequestDispatcher */
        /* @var $other_dispatcher RequestDispatcher */
        /* @var $event AlterEvent */

        $event = AlterEvent::from([

            'target' => &$dispatcher

        ]);

        $this->assertSame($dispatcher, $event->instance);
        $event->instance = $other_dispatcher;
        $this->assertSame($other_dispatcher, $dispatcher);
    }

    public function test_insert_before()
    {
        $id = uniqid();
        $reference = uniqid();
        $inserted_dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'offsetSet' ])
            ->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('offsetSet');

        /* @var $event AlterEvent */

        $event = AlterEvent::from([

            'target' => &$dispatcher

        ]);

        $event->insert_before($id, $inserted_dispatcher, $reference);
    }

    public function test_insert_after()
    {
        $id = uniqid();
        $reference = uniqid();
        $inserted_dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this
            ->getMockBuilder(RequestDispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'offsetSet' ])
            ->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('offsetSet');

        /* @var $event AlterEvent */

        $event = AlterEvent::from([

            'target' => &$dispatcher

        ]);

        $event->insert_after($id, $inserted_dispatcher, $reference);
    }
}
