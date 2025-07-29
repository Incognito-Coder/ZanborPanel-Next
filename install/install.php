<?php

function request($url)
{
	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 30,
	]);
	$response = curl_exec($ch);
	if ($response === false) {
		error_log('cURL Error: ' . curl_error($ch));
	}
	curl_close($ch);
	return $response;
}

if (
	isset($_POST['token'], $_POST['admin-id'], $_POST['db-name'], $_POST['db-user'], $_POST['db-pass'])
) {
	if (!file_exists('zanbor.install')) {
		if (
			file_exists('../config.php') &&
			file_exists('../index.php') &&
			file_exists('../texts.json') &&
			file_exists('../sql/sql.php')
		) {
			// Improved domain calculation
			$script_filename = $_SERVER['SCRIPT_FILENAME'];
			$public_html_pos = strpos($script_filename, 'public_html/');
			if ($public_html_pos !== false) {
				$relative_path = substr($script_filename, $public_html_pos + strlen('public_html/'));
				$domain = 'https://' . $_SERVER['HTTP_HOST'] . '/' . explode('/', $relative_path)[0];
			} else {
				$domain = 'https://' . $_SERVER['HTTP_HOST'];
			}

			$getTokenStatus = json_decode(request('https://api.telegram.org/bot' . $_POST['token'] . '/getMe'), true)['ok'] ?? false;
			if ($getTokenStatus) {
				$config_file = file_get_contents('../config.php');

				$replace = str_replace(
					['[*TOKEN*]', '[*DEV*]', '[*DB-NAME*]', '[*DB-USER*]', '[*DB-PASS*]'],
					[$_POST['token'], $_POST['admin-id'], $_POST['db-name'], $_POST['db-user'], $_POST['db-pass']],
					$config_file
				);
				file_put_contents('../config.php', $replace);

				$webhook = json_decode(request('https://api.telegram.org/bot' . $_POST['token'] . '/setWebhook?url=' . $domain . '/index.php'), true);
				if (!($webhook['ok'] ?? false)) {
					file_put_contents('../config.php', $config_file);
					die('<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">عملیات انجام ست وبهوک با خطا مواجه شد ❌</h2>');
				}

				$connect = json_decode(request($domain . '/sql/sql.php?db_name=' . $_POST['db-name'] . '&db_username=' . $_POST['db-user'] . '&db_password=' . $_POST['db-pass']), true);
				if (!($connect['status'] ?? false)) {
					file_put_contents('../config.php', $config_file);
					die('<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">اطلاعات دیتابیس ارسالی شما اشتباه است ❌</h2>');
				}

				$install_data = [
					'development' => '@ZanborPanel',
					'install_location' => 'host',
					'main_domin' => $domain,
					'token' => $_POST['token'],
					'dev' => $_POST['admin-id'],
					'db_name' => $_POST['db-name'],
					'db_username' => $_POST['db-user'],
					'db_password' => $_POST['db-pass']
				];
				file_put_contents('zanbor.install', json_encode($install_data, JSON_UNESCAPED_UNICODE));
				// Optionally restrict permissions
				@chmod('zanbor.install', 0600);

				$send_message = json_decode(request('https://api.telegram.org/bot' . $_POST['token'] . '/sendMessage?chat_id=' . $_POST['admin-id'] . '&text=' . urlencode(base64_decode('CuKchSDYsdio2KfYqiDYqNinINmF2YjZgdmC24zYqiDZhti12Kgg2LTYry4KCvCfmoAg2LHYqNin2Kog2LHYpyAvc3RhcnQg2qnZhtuM2K8uCgrwn5CdIC0gQFphbmJvclBhbmVsIC0gQFphbmJvclBhbmVsR2FwCg=='))), true);
				print '<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">ربات شما با موفقیت نصب شد ✅</h2>';
			} else {
				print '<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">توکن ارسالی اشتباه است ❌</h2>';
			}
		} else {
			print '<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">فایل های اصلی ربات یافت نشد ❌</h2>';
		}
	} else {
		print '<h2 style="text-align: center; color: black; font-size: 32px; margin-top: 50px;">ربات قبلا یک بار نصب شده است ❌</h2>';
	}
} else {
	print '<h2 style="text-align: center; color: black; font-size: 40px; margin-top: 60px;">مقادیر اجباری به درستی ارسال نشده است ❌</h2>';
}
