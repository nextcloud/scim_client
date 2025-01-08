<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserCreatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserCreatedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		$params = [
			'event' => 'UserCreatedEvent',
			'user_id' => $event->getUid(),
			'password' => $event->getPassword(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
