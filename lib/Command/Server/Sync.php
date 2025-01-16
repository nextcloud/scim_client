<?php

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimServerService;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command {
	public function __construct(
		private readonly ScimApiService $scimApiService,
		private readonly ScimServerService $scimServerService,
		private readonly ICrypto $crypto,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(Application::APP_ID . ':server:sync')
			->addArgument('name', InputArgument::REQUIRED, 'Server name')
			->setDescription('Push all Nextcloud users and groups to an SCIM server');
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
		try {
			$name = $input->getArgument('name');
			$server = $this->scimServerService->getScimServerByName($name)->jsonSerialize();
			$server['api_key'] = $this->crypto->decrypt($server['api_key']);
			$this->scimApiService->syncScimServer($server);
		} catch (\Exception $e) {
			$output->writeln('<error>Failed to sync server</error>');
			$output->writeln($e->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}
