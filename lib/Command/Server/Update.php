<?php

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimServerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
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
		$this->setName(Application::APP_ID . ':server:update')
			->addArgument('name', InputArgument::REQUIRED, 'Name of server to update')
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'Server name')
			->addOption('url', null, InputOption::VALUE_REQUIRED, 'Server URL')
			->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'Server API key')
			->setDescription('Update SCIM server');
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
		$serverName = $input->getArgument('name');
		$server = $this->scimServerService->getScimServerByName($serverName);
		if (!$server) {
			$output->writeln(sprintf('SCIM server %s not found.', $serverName));
			return Command::FAILURE;
		}

		$name = $input->getOption('name');
		if ($name) {
			$server->setName($name);
		}

		$url = $input->getOption('url');
		if ($url) {
			if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
				$output->writeln('Failed to register SCIM server. URL must start with `http://` or `https://`.');
				return Command::FAILURE;
			}

			$server->setUrl(rtrim($url, '/'));
		}

		$apiKey = $input->getOption('api-key');
		if ($apiKey) {
			$server->setApiKey($apiKey);
		}

		$server = $this->scimServerService->updateScimServer($server);
		if (!$server) {
			$output->writeln('Failed to update SCIM server.');
			return Command::FAILURE;
		}

		$output->writeln('SCIM server successfully updated.');
		return Command::SUCCESS;
	}
}
