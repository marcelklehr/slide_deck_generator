<?php
/**
 * Nextcloud - ContextChat
 *
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2023
 */

namespace OCA\SlideDeckGenerator\AppInfo;

use OCA\SlideDeckGenerator\Listener\AppDisableListener;
use OCA\SlideDeckGenerator\Listener\FileListener;
use OCA\SlideDeckGenerator\Service\ProviderConfigService;
use OCA\SlideDeckGenerator\TaskProcessing\SlideDeckGenerationProvider;
use OCA\SlideDeckGenerator\TaskProcessing\SlideDeckGenerationTaskType;
use OCP\App\Events\AppDisableEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Events\NodeRemovedFromCache;
use OCP\IConfig;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;

class Application extends App implements IBootstrap {

	public const APP_ID = 'slide_deck_generator';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
        $context->registerTaskProcessingProvider(SlideDeckGenerationProvider::class);
        $context->registerTaskProcessingTaskType(SlideDeckGenerationTaskType::class);
    }

	public function boot(IBootContext $context): void {
	}
}
