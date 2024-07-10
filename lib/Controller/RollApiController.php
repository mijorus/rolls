<?php

declare(strict_types=1);

namespace OCA\Rolls\Controller;

use OCA\Rolls\Db\Roll;
use OCA\Rolls\Db\RollsDb;
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
class RollApiController extends ApiController {
	private IUserSession $session;
	private IRootFolder $storage;
	private IUserManager $userManager;
	protected RollsDb $rollsDb;

	public function __construct($appName, RollsDb $rollsDb, IRequest $request, IUserSession $session, IRootFolder $rootFolder, IUserManager $userManager) {
		parent::__construct($appName, $request, $session);
		$this->session = $session;
		$this->storage = $rootFolder;
		$this->rollsDb = $rollsDb;
		$this->userManager = $userManager;
	}

	/**
	 * An example API endpoint
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/hello')]
	public function index(): JSONResponse {
		return new JSONResponse(
			['message' => 'Hello world!', 'data' => null]
		);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUploadPath(): JSONResponse {
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
	public function createRoll(): JSONResponse {
		$user = $this->session->getUser();

		$userFolder = $this->storage->getUserFolder($user->getUID());

		$videoFolderName = $userFolder->getPath() . '/Rolls';

		$exists = $this->storage->nodeExists($videoFolderName);

		if (!$exists) {
			$this->storage->newFolder($videoFolderName);
		}

		$uuid = Uuid::v4()->__toString();
		$videoFolder = $this->storage->get($videoFolderName);

		$ext = $this->request->getParam('ext', 'webm');
		$tbExt = $this->request->getParam('tbExt', 'png');

		$foldernameBase = $videoFolder->getPath() . '/Roll_' . $uuid;
		$folderName = $foldernameBase;

		$i = 1;
		while ($this->storage->nodeExists($folderName)) {
			$folderName = $foldernameBase . '-' . $i;
			$i += 1;
		}

		$folder = $this->storage->newFolder($folderName);
		$tbFolder = $folder->newFolder('/.thumbnails');

		$videoFilename = 'Roll-' . $uuid . '.' . $ext;
		$filename = $folder->getPath() . '/' . $videoFilename;

		$requestFile = fopen($this->request->getUploadedFile('video')['tmp_name'], 'r');
		$thumbFile = fopen($this->request->getUploadedFile('thumbnail')['tmp_name'], 'r');
		$requestText = file_get_contents($this->request->getUploadedFile('text')['tmp_name']);

		$videoFile = $this->storage->newFile($filename, $requestFile);
		$tbFolder->newFile($videoFilename . '.' . $tbExt, $thumbFile);

		if (strlen($requestText) > 0) {
			$this->storage->newFile($folder->getPath() . '/README.md', $requestText);
		}

		$roll = $this->rollsDb->create($uuid, $videoFile->getId(), $videoFolder->getId(), $user->getUID());

		return new JSONResponse(
			['data' => [
				'uuid' => $roll->getUuid(),
				'fileId' => $roll->getVideoFile(),
			]]
		);
	}

	#[NoAdminRequired]
	public function getRolls(): JSONResponse {
		$user = $this->session->getUser();

		$entities = [];
		$uuid = $this->request->getParam('uuid');

		if ($uuid) {
			$result = $this->rollsDb->find($uuid);

			if ($result) {
				$entities[] = $result;
			}
		} else {
			$entities = $this->rollsDb->findAll($user);
		}

		$userFolder = $this->storage->getUserFolder($user->getUID());

		$response = [];
		foreach ($entities as $entity) {
			$id = intval($entity->getVideoFile());

			$files = null;

			try {
				$files = $userFolder->getById($id);
			} catch (\OCP\Files\NotFoundException $e) {
				continue;
			} catch (\Exception $e) {
				throw $e;
			}

			if (!count($files)) {
				continue;
			}

			// TODO:
			$file = $files[0];

			if ($file instanceof \OCP\Files\File) {
				$text = null;
				$thumbnailNode = null;
				$textFileNode = null;

				try {
					$textFileNode = $this->storage->get($file->getParent()->getPath() . '/README.md');
					$text = $textFileNode->getContent();
				} catch (\OCP\Files\NotFoundException $e) {
					// pass
				}

				$tbPath = $file->getParent()->getPath() . '/.thumbnails';
				if ($this->storage->nodeExists($tbPath)) {
					$tbFolder = $this->storage->get($tbPath);
					$thumbnails = $tbFolder->getDirectoryListing();

					foreach ($thumbnails as $t) {
						// Check if a thumbnail for the given file exists,
						// The thumbnail MUST have the same filename minus the extension
						$tm = pathinfo($t->getPath(), PATHINFO_FILENAME);
						$fm = basename($file->getPath());

						if ($tm === $fm) {
							$thumbnailNode = $t;
							break;
						}
					}
				}

				$thumbnail = Funcs::getUserFilePath($user, $this->storage, $thumbnailNode);
				$textPath = Funcs::getUserFilePath($user, $this->storage, $textFileNode);
				$path = Funcs::getUserFilePath($user, $this->storage, $file);
				$creationDate = $entity->getCreatedAt() ?? $file->getMtime();
				$owner = $this->userManager->get($entity->getOwner());

				$response[] = [
					'owner' => [
						'displayName' => $owner->getDisplayName(),
						'id' => $owner->getUID(),
					],
					'isMine' => $entity->getOwner() === $user->getUID(),
					'creationDate' => $creationDate,
					'uuid' => $entity->getUuid(),
					'file' => [
						'id' => $file->getId(),
						'path' => $path,
					],
					'text' => $text,
					'textFile' => $textFileNode ? [
						'id' => $textFileNode->getId(),
						'path' => $textPath
					] : null,
					'thumbnail' => $thumbnail,
				];
			}
		}

		return new JSONResponse([
			'data' => $response,
		]);
	}

	#[NoAdminRequired]
	public function deleteRoll(): Response {
		$uuid = $this->request->getParam('id');
		$user = $this->session->getUser();

		if (empty($uuid)) {
			return new JSONResponse([
				'message' => 'Missing uuid'
			], Http::STATUS_NOT_FOUND);
		}

		$roll = $this->rollsDb->find($uuid);

		if ($roll->getOwner() !== $user->getUID()) {
			return new Response(Http::STATUS_UNAUTHORIZED);
		}

		if (!$roll) {
			return new JSONResponse([
				'message' => 'Item not found in database'
			], Http::STATUS_NOT_FOUND);
		}

		// TODO:
		$nodes = $this->storage->getById(intval($roll->getVideoFolder()));

		foreach ($nodes as $node) {
			$node->delete();
		}

		$this->rollsDb->delete($roll);
		return new Response(Http::STATUS_OK);
	}
}
