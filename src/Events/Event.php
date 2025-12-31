<?php

namespace App\Events;

/**
 * Event - Base interface for domain events
 * 
 * Domain events represent significant occurrences in the business domain.
 * They enable loose coupling between services and trigger side effects.
 * 
 * @example
 * class OrderCreated implements Event {
 *     public function __construct(public Order $order) {}
 * }
 */
interface Event
{
    // Marker interface
}
