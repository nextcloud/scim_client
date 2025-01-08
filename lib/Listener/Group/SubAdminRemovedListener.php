<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\SubAdminRemovedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class SubAdminRemovedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof SubAdminRemovedEvent)) {
			return;
		}

		$params = [
			'event' => 'SubAdminRemovedEvent',
			'group_id' => $event->getGroup()->getGID(),
			'user_id' => $event->getUser()->getUID(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
