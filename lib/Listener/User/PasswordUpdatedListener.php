<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class PasswordUpdatedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		// TODO: user password updated listener
	}
}
