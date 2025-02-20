<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Listener\Group;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserRemovedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$params = [
			'event' => 'UserRemovedEvent',
			'group_id' => $event->getGroup()->getGID(),
			'user_id' => $event->getUser()->getUID(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
