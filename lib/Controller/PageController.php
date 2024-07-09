<?php

declare(strict_types=1);

namespace OCA\Rolls\Controller;

use Bamarni\Composer\Bin\Logger;
use OCA\Rolls\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\Viewer\Event\LoadViewer;
use OCA\Text\Event\LoadEditor;
use OCP\IRequest;

class PageController extends Controller {
	protected $appName;
	private IEventDispatcher $eventDispatcher;


	public function __construct(
		$appName,
		IRequest $request,
		IEventDispatcher $eventDispatcher
	) {
		parent::__construct($appName, $request);

		$this->appName = $appName;
		$this->eventDispatcher = $eventDispatcher;
	}


	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		$response = new TemplateResponse(
			Application::APP_ID,
			'index',
		);

		if (class_exists(LoadEditor::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadEditor());
		}

		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}
		
		$response->addHeader('Feature-Policy', "");

		return $response;
	}
}
