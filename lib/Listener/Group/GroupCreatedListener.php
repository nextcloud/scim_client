<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class GroupCreatedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupCreatedEvent)) {
			return;
		}

		$params = [
			'event' => 'GroupCreatedEvent',
			'group_id' => $event->getGroup()->getGID(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
