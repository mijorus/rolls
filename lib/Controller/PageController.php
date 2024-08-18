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
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DownloadResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Http\WellKnown\GenericResponse;
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

	// #[NoCSRFRequired]
	// #[NoAdminRequired]
	// #[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	// #[FrontpageRoute(verb: 'GET', url: '/phpinfo')]
	// public function info() {
	// 	phpinfo();
	// 	die;
	// }
	
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/static')]
	public function static(string $path) {
		$staticPath = __DIR__ . '/../../static';
		$staticFiles = array_diff(scandir($staticPath), ['.', '..']);
		
		if (!in_array($path, $staticFiles)) {
			return new NotFoundResponse();
		}
		
		$path = $staticPath . '/' . $path;
		$ct = filetype($path);

		$response = new DownloadResponse($path, $ct);
		// $response->setHeaders(['content-type' => $ct]);
		
		return $response;
	}
}
