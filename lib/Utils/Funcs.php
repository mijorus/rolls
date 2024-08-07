<?php

namespace OCA\Rolls\Utils;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;

class Funcs
{
	static function getUserFilePath(IUser $user, IRootFolder $rootfolder, ?Node $path): ?string
	{
		if (!$path) {
			return null;
		}

		$userFolder = $rootfolder->getUserFolder($user->getUID());
		return '/' . $user->getUID() . $userFolder->getRelativePath($path->getPath());
	}

	static function joinPaths(...$paths): string
	{
		return implode('/', $paths);
	}

	static function ensureUniqueFileName(IRootFolder $storage, string $pathName): string
	{
		$i = 1;
		while ($storage->nodeExists($pathName)) {
			$pathName = $pathName . '-' . $i;
			$i += 1;
		}

		return $pathName;
	}
}
