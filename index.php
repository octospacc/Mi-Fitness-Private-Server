<?php

function getServerUrl() {
	return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
}

function scandir2($path) {
	return array_diff(scandir($path), ['.', '..']);
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

function getFileNameWithPrefix($folder, $prefix) {
	foreach (scandir2($folder) as $file) {
		if (str_starts_with(strtolower($file), strtolower($prefix))) {
			return $file;
		}
	}
}

function getFilePathWithPrefix($folder, $prefix) {
	if ($file = getFileNameWithPrefix($folder, $type)) {
		return "{$folder}/{$file}";
	}
}

function atomicPath($root, $item, $sep=DIRECTORY_SEPARATOR) {
	if ($root && $item) {
		return $root . $sep . $item;
	}
}

function getItemPath($root, $item) {
	return ($item ? ($root . DIRECTORY_SEPARATOR . $item) : $root);
}

function getItemDir($root, $item) {
	return __DIR__ . DIRECTORY_SEPARATOR . getItemPath($root, $item);
}

function getItemUrl($root, $item) {
	return getServerUrl() . '/' . getItemPath($root, $item);
}

function getWatchFaceDir($model, $item=null) {
	return getItemDir('watchfaces' . DIRECTORY_SEPARATOR . $model, $item);
}

function getWatchFaceUrl($model, $item) {
	return getItemUrl('watchfaces/' . $model, $item);
}

function getWatchAppDir($item=null) {
	return getItemDir('apps', $item);
}

function getWatchAppUrl($item) {
	return getItemUrl('apps', $item);
}

function getItemData($itemPath) {
	$data = [];
	if (file_exists($ini = getFilePathOfType($itemPath, 'ini'))) {
		$data = parse_ini_file($ini);
	} else if (file_exists($json = getFilePathOfType($itemPath, 'json'))) {
		$data = json_decode(file_get_contents($json), true);
	}
	return $data;
}

function getWatchFaceInfo($id, $model) {
	return getWatchFaceList($model, [$id])[0] ?: [];
}

function getWatchFaceList($model, $idList=null) {
	$list = [];
	foreach (scandir2(getWatchFaceDir($model)) as $dir) {
		$url = getWatchFaceUrl($model, $dir);
		$dir = getWatchFaceDir($model, $dir);
		$data = getItemData($dir);
		$data['id'] ??= hash('sha256', $data['display_name']);
		$data['id_v2'] ??= $data['id'];
		if (!$idList || (in_array($data['id'], $idList) || in_array($data['id_v2'], $idList))) {
			$icon = $data['icon'] ?: getFileNameOfType($dir, 'png') ?: getFileNameOfType($dir, 'jpg');
			$data['aod_icon'] = $url . '/' . (getFileNameWithPrefix($dir, 'aod-preview_') ?: $data['aod_icon'] ?: $icon);
			$data['icon'] = $url . '/' . (getFileNameWithPrefix($dir, 'market-preview_') ?: $icon);
			$data['icon_list'] = [
				[
					'icon'     => $data['icon'],
					'aod_icon' => $data['aod_icon'],
				],
			];
			// NOTE: apparently we can't install BIN (v1) faces on newer app versions; they also error if forced as v2
			$file = getFilePathOfType($dir, 'bin');
			$file_v2 = getFilePathOfType($dir, 'zip');
			$data['config_file'] = $data['config_file_v2'] = '';
			if ($file) {
				$data['config_file'] = atomicPath($url, getFileNameOfType($dir, 'bin'));
				$data['file_hash'] ??= hash_file('md5', $file);
				$data['file_size'] ??= filesize($file);
			}
			if ($file_v2) {
				$data['config_file_v2'] = atomicPath($url, getFileNameOfType($dir, 'zip')) ?: '';
				$data['file_hash_v2'] ??= hash_file('md5', $file_v2);
				$data['file_size_v2'] ??= filesize($file_v2);
			}
			array_push($list, $data);
		}
	}
	return $list;
}

function getFeedWatchFaceList($model, $page) {
	return getWatchFaceList($model);
}

function getWatchAppList() {
	$list = [];
	foreach (scandir2(getWatchAppDir()) as $dir) {
		$tokens = explode('_', $dir);
		$url = getWatchAppUrl($dir);
		$dir = getWatchAppDir($dir);
		$data = getItemData($dir);
		$data['app_id'] ??= $tokens[1] ? $tokens[0] : 0;
		$data['package_name'] ??= $tokens[1] ?: $tokens[0];
		$data['app_name'] ??= $tokens[2] ?: $tokens[1] ?: $tokens[0];
		$data['update_time'] ??= time();
		$data['icon'] = $url . '/' . getFileNameOfType($dir, 'png');
		$data['download_url'] = $url . '/' . getFileNameOfType($dir, 'rpk');
		$data['size'] ??= (int)(filesize(getFilePathOfType($dir, 'rpk')) / 1000);
		array_push($list, $data);
	}
	return $list;
}

function getDeviceConfig($model) {
	switch ($model) {
		case 'miwear.watch.n66gl':
			return [
				'shape' => 2,
			];
	}
}

function apiResponse($result) {
	return json_encode([
		'code'   => 200,
		'result' => $result,
	]);
}

$path = parse_url($_SERVER['REQUEST_URI'])['path'];
$data = json_decode($_GET['data'], true);

if (preg_match('@/api/v2/paidwatchface/|/micolor/api/@', $path)) {
	header('Content-Type: application/json; charset=utf-8');
}

switch ($path) {
	case '/api/v2/paidwatchface/list':
		// Device > Manage band displays > Local
		echo apiResponse([
			'watchface_list' => getWatchFaceList($data['model'], $data['id_list']),
		]);
		exit;
	case '/api/v2/paidwatchface/index':
		// Device > Manage band displays > Online
		echo apiResponse([
			'device_config'       => getDeviceConfig($data['model']),
			'feed_watchface_list' => getFeedWatchFaceList($data['model'], 1),
			'has_more'            => false,
		]);
		exit;
	case '/api/v2/paidwatchface/detail':
		// Device > Manage band displays > [Watchface]
		echo apiResponse([
			'watch_face' => getWatchFaceInfo($data['id'], $data['model']),
		]);
		exit;
	case '/api/v2/paidwatchface/download':
		// Device > Manage band displays > [Watchface] > Apply
		echo apiResponse([
			'download_info' => getWatchFaceInfo($data['id'], $data['model']),
			'license'       => '{}',
			'sign'          => '',
		]);
		exit;
	case '/micolor/api/get_watch_app_info':
	case '/micolor/api/get_watch_app_list':
		// Device > Apps > Apps
		echo apiResponse([
			'watch_app_list' => getWatchAppList(),
		]);
		exit;
	default:
		http_response_code(404);
}

