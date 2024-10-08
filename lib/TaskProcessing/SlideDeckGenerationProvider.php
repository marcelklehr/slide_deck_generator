<?php

declare(strict_types=1);

namespace OCA\SlideDeckGenerator\TaskProcessing;

use OCA\SlideDeckGenerator\AppInfo\Application;
use OCA\SlideDeckGenerator\Service\SlideDeckService;
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
		private SlideDeckService $slideDeckService,
		private IL10N            $l10n,
		private IRootFolder      $rootFolder,
		private LoggerInterface  $logger,
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

		$response = $this->slideDeckService->generateSlideDeck(
			$userId,
			$input['text'],
		);

		return $response;
	}
}
