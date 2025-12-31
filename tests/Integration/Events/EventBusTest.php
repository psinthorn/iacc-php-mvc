<?php

namespace Tests\Integration\Events;

use Tests\TestCase;
use App\Events\EventBus;

/**
 * Event Bus Integration Tests
 */
class EventBusTest extends TestCase
{
    protected $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = new EventBus();
    }

    /**
     * Test event listener is triggered
     */
    public function testEventListenerIsTriggered()
    {
        $eventFired = false;

        $this->eventBus->listen('user.created', function() use (&$eventFired) {
            $eventFired = true;
        });

        $this->eventBus->dispatch('user.created');

        $this->assertTrue($eventFired);
    }

    /**
     * Test event listener receives event data
     */
    public function testEventListenerReceivesData()
    {
        $receivedData = null;

        $this->eventBus->listen('user.created', function($data) use (&$receivedData) {
            $receivedData = $data;
        });

        $userData = ['id' => 1, 'name' => 'John'];
        $this->eventBus->dispatch('user.created', $userData);

        $this->assertEquals($userData, $receivedData);
    }

    /**
     * Test multiple listeners for same event
     */
    public function testMultipleListenersForSameEvent()
    {
        $listener1Called = false;
        $listener2Called = false;

        $this->eventBus->listen('order.completed', function() use (&$listener1Called) {
            $listener1Called = true;
        });

        $this->eventBus->listen('order.completed', function() use (&$listener2Called) {
            $listener2Called = true;
        });

        $this->eventBus->dispatch('order.completed');

        $this->assertTrue($listener1Called);
        $this->assertTrue($listener2Called);
    }

    /**
     * Test can remove listener
     */
    public function testCanRemoveListener()
    {
        $listenerCalled = false;

        $listener = function() use (&$listenerCalled) {
            $listenerCalled = true;
        };

        $this->eventBus->listen('user.deleted', $listener);
        $this->eventBus->removeListener('user.deleted', $listener);

        $this->eventBus->dispatch('user.deleted');

        $this->assertFalse($listenerCalled);
    }

    /**
     * Test listener can modify event data
     */
    public function testListenerCanModifyData()
    {
        $data = ['status' => 'pending'];

        $this->eventBus->listen('order.created', function(&$eventData) {
            $eventData['status'] = 'processing';
        });

        $this->eventBus->dispatch('order.created', $data);

        $this->assertEquals('processing', $data['status']);
    }

    /**
     * Test event with callback response
     */
    public function testEventWithCallbackResponse()
    {
        $this->eventBus->listen('payment.process', function() {
            return 'payment_processed';
        });

        $result = $this->eventBus->dispatch('payment.process');

        $this->assertIsArray($result);
    }

    /**
     * Test listener for wildcard events
     */
    public function testWildcardEventListeners()
    {
        $userEventFired = false;

        $this->eventBus->listen('user.*', function() use (&$userEventFired) {
            $userEventFired = true;
        });

        $this->eventBus->dispatch('user.created');

        $this->assertTrue($userEventFired);
    }

    /**
     * Test event order preservation
     */
    public function testEventOrderPreservation()
    {
        $order = [];

        $this->eventBus->listen('test.event', function() use (&$order) {
            $order[] = 'first';
        });

        $this->eventBus->listen('test.event', function() use (&$order) {
            $order[] = 'second';
        });

        $this->eventBus->dispatch('test.event');

        $this->assertEquals(['first', 'second'], $order);
    }

    /**
     * Test event without listeners doesn't error
     */
    public function testEventWithoutListenersDoesntError()
    {
        $this->eventBus->dispatch('nonexistent.event');

        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test listener can stop event propagation
     */
    public function testListenerCanStopPropagation()
    {
        $listener1Called = false;
        $listener2Called = false;

        $this->eventBus->listen('stop.test', function() use (&$listener1Called) {
            $listener1Called = true;
            return false; // Stop propagation
        });

        $this->eventBus->listen('stop.test', function() use (&$listener2Called) {
            $listener2Called = true;
        });

        $this->eventBus->dispatch('stop.test');

        $this->assertTrue($listener1Called);
        $this->assertFalse($listener2Called);
    }
}
