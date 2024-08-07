<?php

declare(strict_types=1);

namespace OCA\Rolls\Controller;

use OCA\Rolls\Db\Roll;
use OCA\Rolls\Db\RollsDb;
use OCA\Rolls\Entity\RollListItem;
use OCA\Rolls\Utils\Funcs;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;
use Symfony\Component\Uid\Uuid;

/**
 * @psalm-suppress UnusedClass
 */
class RollApiController extends ApiController
{
	private IUserSession $session;
	private IRootFolder $storage;
	private IUserManager $userManager;
	private RollService $service;
	private RollsDb $rollsDb;

	public function __construct($appName, RollsDb $rollsDb, IRequest $request, IUserSession $session, IRootFolder $rootFolder, IUserManager $userManager, RollService $rollService)
	{
		parent::__construct($appName, $request, $session);
		$this->session = $session;
		$this->storage = $rootFolder;
		$this->rollsDb = $rollsDb;
		$this->userManager = $userManager;
		$this->service = $rollService;
	}

	/**
	 * An example API endpoint
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/hello')]
	public function index(): JSONResponse
	{
		return new JSONResponse(
			['message' => 'Hello world!', 'data' => null]
		);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUploadPath(): JSONResponse
	{
		$user = $this->session->getUser();
		$userFolder = $this->storage->getUserFolder($user->getUID());
		$videoFolder = 'Rolls';

		$node_path = $userFolder->getPath() . '/' . $videoFolder;
		$app_path = $user->getUID() . '/' . $videoFolder;

		$exists = $this->storage->nodeExists($node_path);

		if (!$exists) {
			$this->storage->newFolder($node_path);
		}

		return new JSONResponse(
			['data' => $app_path]
		);
	}

	#[NoAdminRequired]
	public function createRoll(): JSONResponse
	{
		$requestFile = fopen($this->request->getUploadedFile('video')['tmp_name'], 'r');
		$thumbFile = fopen($this->request->getUploadedFile('thumbnail')['tmp_name'], 'r');
		$requestText = file_get_contents($this->request->getUploadedFile('text')['tmp_name']);

		$ext = $this->request->getParam('ext', 'webm');
		$tbExt = $this->request->getParam('tbExt', 'png');

		$roll = $this->service->createRoll($requestFile, $ext, $thumbFile, $tbExt, $requestText);

		return new JSONResponse(
			['data' => [
				'uuid' => $roll->getUuid(),
				'fileId' => $roll->getVideoFile(),
			]]
		);
	}

	#[NoAdminRequired]
	public function getRolls(): JSONResponse
	{
		$user = $this->session->getUser();

		$items = $this->service->getRolls($this->request->getParam('uuid'));

		$response = [];
		foreach ($items as $item) {
			if (!($item instanceof RollListItem)) {
				continue;
			}

			$thumbnail = Funcs::getUserFilePath($user, $this->storage, $item->thumbnail);
			$textPath = Funcs::getUserFilePath($user, $this->storage, $item->textFile);
			$path = Funcs::getUserFilePath($user, $this->storage, $item->file);
			$creationDate = $item->roll->getCreatedAt() ?? $item->file->getMtime();
			$owner = $this->userManager->get($item->roll->getOwner());
			$text = $item->textFile ? $item->textFile->getContent() : null;


			$response[] = [
				'owner' => [
					'displayName' => $owner->getDisplayName(),
					'id' => $owner->getUID(),
				],
				'isMine' => $item->roll->getOwner() === $user->getUID(),
				'creationDate' => $creationDate,
				'uuid' => $item->roll->getUuid(),
				'file' => [
					'id' => $item->file->getId(),
					'path' => $path,
				],
				'text' => $text,
				'textFile' => $item->textFile ? [
					'id' => $item->textFile->getId(),
					'path' => $textPath
				] : null,
				'thumbnail' => $thumbnail,
			];
		}

		return new JSONResponse([
			'data' => $response,
		]);
	}

	#[NoAdminRequired]
	public function deleteRoll(string $uuid): Response
	{
		if (empty($uuid)) {
			return new JSONResponse([
				'message' => 'Missing uuid'
			], Http::STATUS_NOT_FOUND);
		}

		$user = $this->session->getUser();
		$roll = $this->rollsDb->find($uuid);

		if ($roll->getOwner() !== $user->getUID()) {
			return new Response(Http::STATUS_UNAUTHORIZED);
		}

		if (!$roll) {
			return new JSONResponse([
				'message' => 'Item not found in database'
			], Http::STATUS_NOT_FOUND);
		}

		$this->service->deleteRoll($roll);

		return new Response(Http::STATUS_OK);
	}
}
