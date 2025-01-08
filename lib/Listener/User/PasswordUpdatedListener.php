<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class PasswordUpdatedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}

		$params = [
			'event' => 'PasswordUpdatedEvent',
			'user_id' => $event->getUser()->getUID(),
			'password' => $event->getPassword(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
