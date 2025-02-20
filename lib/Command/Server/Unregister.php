<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimServerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Unregister extends Command {
	public function __construct(
		private readonly ScimServerService $scimServerService,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(Application::APP_ID . ':server:unregister')
			->addArgument('name', InputArgument::REQUIRED, 'Server name')
			->setDescription('Unregister SCIM server');
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$server = $this->scimServerService->getScimServerByName($name);
		if (!$server) {
			$output->writeln(sprintf('SCIM server %s not found.', $name));
			return Command::FAILURE;
		}

		$server = $this->scimServerService->unregisterScimServer($server);
		if (!$server) {
			$output->writeln(sprintf('Failed to unregister SCIM server %s.', $name));
			return Command::FAILURE;
		}

		$output->writeln('SCIM server successfully unregistered.');
		return Command::SUCCESS;
	}
}
