<?php

function getServerUrl() {
	return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
}

function scandir2($path) {
	return array_diff(scandir($path), array('.', '..'));
}

function getFileNameOfType($folder, $type) {
	foreach (scandir2($folder) as $file) {
		if (str_ends_with(strtolower($file), strtolower(".{$type}"))) {
			return $file;
		}
	}
}

function getFilePathOfType($folder, $type) {
	if ($file = getFileNameOfType($folder, $type)) {
		return "{$folder}/{$file}";
	}
}

function makeSafeFilePath($baseDir, $fileName) {
	$fullPath = $baseDir . '/' . str_replace(['..', './', '\\'], '', $fileName);
	if (realpath(dirname($fullPath)) !== realpath($baseDir) && strpos($fullPath, realpath($baseDir)) !== 0) {
		die;
	}
	return $fullPath;
}

$path = parse_url($_SERVER['REQUEST_URI'])['path'];

if (preg_match('@/apps/|/watchfaces/@', $path)) {
	return file_get_contents(makeSafeFilePath(__DIR__, $path));
	exit;
}

if (preg_match('@/api/v2/paidwatchface/|/micolor/api/@', $path)) {
	header('Content-Type: application/json; charset=utf-8');
}

function getWatchAppList() {
	$appList = [];
	foreach (scandir2(__DIR__ . '/apps/') as $appDir) {
		$appData = [];
		if (file_exists($appJson = getFilePathOfType(__DIR__ . '/apps/' . $appDir, 'json'))) {
			$appData = json_decode(file_get_contents($appJson));
		} else if (file_exists($appIni = getFilePathOfType(__DIR__ . '/apps/' . $appDir, 'ini'))) {
			$appData = parse_ini_file($appIni);
		}
		$appTokens = explode('_', $appDir);
		$appData['app_id'] ??= $appTokens[0];
		$appData['package_name'] ??= $appTokens[1];
		$appData['app_name'] ??= $appTokens[2] ?: $appTokens[1];
		$appData['update_time'] ??= time();
		$appData['icon'] = getServerUrl() . '/apps/' . $appDir . '/' . getFileNameOfType(__DIR__ . '/apps/' . $appDir, 'png');
		$appData['download_url'] = getServerUrl() . '/apps/' . $appDir . '/' . getFileNameOfType(__DIR__ . '/apps/' . $appDir, 'rpk');
		$appData['size'] ??= (int)(filesize(getFilePathOfType(__DIR__ . '/apps/' . $appDir, 'rpk')) / 1000);
		array_push($appList, $appData);
	}
	return $appList;
}

switch ($path) {
	case '/micolor/api/get_watch_app_info':
	case '/micolor/api/get_watch_app_list':
		echo json_encode([
			'code' => 0,
			'message' => 'ok',
			'result' => [
				'watch_app_list' => getWatchAppList(),
			],
		]);
		exit;
}

