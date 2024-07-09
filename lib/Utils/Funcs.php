<?php

namespace OCA\Rolls\Utils;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

class Funcs {
	static function getUserFilePath(IUser $user, IRootFolder $rootfolder, ?Node $path) : ?string {
		if (!$path) {
			return null;
		}
		
		$userFolder = $rootfolder->getUserFolder($user->getUID());
		return '/' . $user->getUID() . $userFolder->getRelativePath($path->getPath());
	}
}