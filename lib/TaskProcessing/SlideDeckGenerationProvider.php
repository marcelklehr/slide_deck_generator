<?php

declare(strict_types=1);

namespace OCA\SlideDeckGenerator\TaskProcessing;

use OCA\SlideDeckGenerator\AppInfo\Application;
use OCA\SlideDeckGenerator\Service\SlideDeckService;
use OCA\SlideDeckGenerator\Type\Source;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\TaskProcessing\ISynchronousProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SlideDeckGenerationProvider implements ISynchronousProvider {

	public function __construct(
		private SlideDeckService $langRopeService,
		private IL10N            $l10n,
		private IRootFolder      $rootFolder,
		private LoggerInterface  $logger,
		private ScanService      $scanService,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '-slide_deck_generator';
	}

	public function getName(): string {
		return $this->l10n->t('Nextcloud Assistant Slide Deck Generator');
	}

	public function getTaskTypeId(): string {
		return SlideDeckGenerationTaskType::ID;
	}

	public function getExpectedRuntime(): int {
		return 120;
	}

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShape(): array {
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShape(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}
	/**
	 * @inheritDoc
	 */
	public function process(?string $userId, array $input, callable $reportProgress): array {
		if ($userId === null) {
			throw new \RuntimeException('User ID is required to process the prompt.');
		}

		if (!isset($input['text']) || !is_string($input['text'])) {
			throw new \RuntimeException('Invalid input, expected "text" key with string value');
		}

		$response = $this->langRopeService->query(
			$userId,
			$input['prompt'],
			true,
			$input['scopeType'],
			$processedScopes,
		);

		if (isset($response['error'])) {
			throw new \RuntimeException('No result in ContextChat response: ' . $response['error']);
		}

		return $response;
	}

	/**
	 * @param array scopeList
	 * @return array<string> List of scopes that were successfully indexed
	 */
	private function indexFiles(string $userId, string ...$scopeList): array {
		$nodes = [];

		foreach ($scopeList as $scope) {
			if (!str_contains($scope, ProviderConfigService::getSourceId(''))) {
				$this->logger->warning('Invalid source format, expected "sourceId: itemId"');
				continue;
			}

			$nodeId = substr($scope, strlen(ProviderConfigService::getSourceId('')));

			try {
				$userFolder = $this->rootFolder->getUserFolder($userId);
			} catch (NotPermittedException $e) {
				$this->logger->warning('Could not get user folder for user ' . $userId . ': ' . $e->getMessage());
				continue;
			}
			$node = $userFolder->getById(intval($nodeId));
			if (count($node) === 0) {
				$this->logger->warning('Could not find file/folder with ID ' . $nodeId . ', skipping');
				continue;
			}
			$node = $node[0];

			if (!$node instanceof File && !$node instanceof Folder) {
				$this->logger->warning('Invalid source type, expected file/folder');
				continue;
			}

			$nodes[] = [
				'scope' => $scope,
				'node' => $node,
				'path' => $node->getPath(),
			];
		}

		// remove subfolders/files if parent folder is already indexed
		$filteredNodes = $nodes;
		foreach ($nodes as $node) {
			if ($node['node'] instanceof Folder) {
				$filteredNodes = array_filter($filteredNodes, function ($n) use ($node) {
					return !str_starts_with($n['path'], $node['path'] . DIRECTORY_SEPARATOR);
				});
			}
		}

		$indexedSources = [];
		foreach ($filteredNodes as $node) {
			try {
				if ($node['node'] instanceof File) {
					$source = $this->scanService->getSourceFromFile($userId, Application::MIMETYPES, $node['node']);
					$this->scanService->indexSources([$source]);
					$indexedSources[] = $node['scope'];
				} elseif ($node['node'] instanceof Folder) {
					$fileSources = iterator_to_array($this->scanService->scanDirectory($userId, Application::MIMETYPES, $node['node']));
					$indexedSources = array_merge(
						$indexedSources,
						array_map(fn (Source $source) => $source->reference, $fileSources),
					);
				}
			} catch (RuntimeException $e) {
				$this->logger->warning('Could not index file/folder with ID ' . $node['node']->getId() . ': ' . $e->getMessage());
			}
		}

		return $indexedSources;
	}
}
