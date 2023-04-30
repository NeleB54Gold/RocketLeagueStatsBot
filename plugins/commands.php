<?php

# Ignore inline messages (via @)
if ($v->via_bot) die;

# Start RocketLeagueTracker class
$rl = new RocketLeagueTracker($db, $bot);
$bot->username = 'RocketLeagueStats_Bot';

# Private chat with Bot
if ($v->chat_type == 'private' || $v->inline_message_id) {
	if ($bot->configs['database']['status'] && $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	
	# Edit message by inline messages
	if ($v->inline_message_id) {
		$v->message_id = $v->inline_message_id;
		$v->chat_id = 0;
	}
	# Test API
	if ($v->isAdmin() && $v->command == 'test') {
		$data = $rl->getPlayer('NeleB54Gold', 'epic');
		$t = $bot->code(substr(json_encode($data), 0, 2048));
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t);
		}
	}
	# Start message
	elseif (in_array('start', [$v->command, $v->query_data])) {
		if (!isset($user['settings']['platform'])) $user['settings']['platform'] = 'epic';
		$t = $bot->bold('🕹 Rocket League Stats') . PHP_EOL . $bot->italic($tr->getTranslation('startMessage'), 1);
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('searchButton'), 'NeleB54Gold', 'switch_inline_query_current_chat'),
			$bot->createInlineButton($tr->getTranslation('platformButton', [$tr->getTranslation('platformSlug' . $user['settings']['platform'])]), 'sp')
		];
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('aboutButton'), 'about'),
			$bot->createInlineButton($tr->getTranslation('helpButton'), 'help')
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'lang');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Switch platform
	elseif (strpos($v->query_data, 'sp') === 0) {
		if (!isset($user['settings']['platform'])) $user['settings']['platform'] = 'epic';
		if ($v->query_data != 'sp') {
			$new = substr($v->query_data, 2);
			if ($new != $user['settings']['platform'] && in_array($new, $rl->platforms)) {
				$user['settings']['platform'] = $new;
				$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $v->user_id]);
			}
		}
		$t = $bot->bold('🕹 Rocket League Stats') . PHP_EOL . $bot->italic($tr->getTranslation('platformMessage'), 1);
		$formenu = 2;
		$mcount = 0;
		foreach ($rl->platforms as $pslug) {
			if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$check = $user['settings']['platform'] == $pslug ? '✅ ' : '';
			$buttons[$mcount][] = $bot->createInlineButton($check . $tr->getTranslation('platformSlug' . $pslug), 'sp' . $pslug);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Help message
	elseif ($v->command == 'help' || $v->query_data == 'help') {
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('switchInlineMode'), 'start inline');
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('helpMessage');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Inline message
	elseif ($v->command == 'start inline' || $v->query_data == 'start inline') {
		$players = [
			'epic'		=> 'NeleB54Gold',
			'steam'		=> 'Firstkiller',
			'xbl'		=> 'yanxnzz',
			'psn'		=> 'First-Killer-19',
			'switch'	=> 'Nuqqet'
		];
		$formenu = 2;
		$mcount = 0;
		foreach ($rl->platforms as $pslug) {
			if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($tr->getTranslation('platformSlug' . $pslug), $pslug . ' ' . $players[$pslug], 'switch_inline_query_current_chat');
			$examples .= PHP_EOL . $bot->bold('• ' . $tr->getTranslation('platformSlug' . $pslug) . ': ') . $bot->code('@' . $bot->username . ' ' . $pslug . ' ' . $players[$pslug]);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'help');
		$t = $tr->getTranslation('helpInlineMessage', [$bot->username, $examples]);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# About message
	elseif ($v->command == 'about' || $v->query_data == 'about') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('aboutMessage', [explode('-', phpversion(), 2)[0]]);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Change language
	elseif ($v->command == 'lang' || $v->query_data == 'lang' || strpos($v->query_data, 'changeLanguage-') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'fr' => '🇫🇷 Français',
			'it' => '🇮🇹 Italiano',
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($select);
				$user['lang'] = $select;
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$t = '🔡 Select your language';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} 
	# Search player
	else {
		if (strpos($v->query_data, 'pi') === 0) {
			$bot->answerCBQ($v->query_id);
			$v->text = substr($v->query_data, 2);
			unset($v->query_data);
		} elseif (strpos($v->query_data, 'mt') === 0) {
			$bot->answerCBQ($v->query_id);
			$v->text = substr($v->query_data, 2);
			$data = $rl->getPlayer($v->text, $user['settings']['platform']);
			if ($data['data']) {
				if (!is_null($data['data']['userInfo']['customAvatarUrl'])) {
					$url = $data['data']['userInfo']['customAvatarUrl'];
				} elseif (isset($data['data']['platformInfo']['avatarUrl'])) {
					$url = $data['data']['platformInfo']['avatarUrl'];
				} else {
					$url = 'https://telegra.ph/file/360f70097f25cdff092c4.jpg';
				}
				$formenu = 2;
				$mcount = 0;
				foreach ($data['data']['segments'] as $segment) {
					if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
					if ($segment['type'] == 'playlist') $buttons[$mcount][] = $bot->createInlineButton($tr->getTranslation('matchName' . $segment['metadata']['name']), 'ms' . str_replace(' ', '', $segment['metadata']['name']) . ' ' . $v->text);
				}
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('overviewButton'), 'pi' . $v->text);
				$t = $bot->text_link('&#8203;', $url) . $tr->getTranslation('playerMatches', [$data['data']['platformInfo']['platformUserHandle']]);
			} else {
				$t = $tr->getTranslation('playerNotFound');
			}
			if ($v->query_id) {
				$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', false);
			} else {
				$bot->sendMessage($v->chat_id, $t, $buttons, 'def', false);
			}
			die;
		} elseif (strpos($v->query_data, 'ms') === 0) {
			$bot->answerCBQ($v->query_id);
			$e = explode(' ', substr($v->query_data, 2), 2);
			$data = $rl->getPlayer($e[1], $user['settings']['platform']);
			if ($data['data']) {
				if (!is_null($data['data']['userInfo']['customAvatarUrl'])) {
					$url = $data['data']['userInfo']['customAvatarUrl'];
				} elseif (isset($data['data']['platformInfo']['avatarUrl'])) {
					$url = $data['data']['platformInfo']['avatarUrl'];
				} else {
					$url = 'https://telegra.ph/file/360f70097f25cdff092c4.jpg';
				}
				foreach ($data['data']['segments'] as $segment) {
					if ($e[0] === str_replace(' ', '', $segment['metadata']['name'])) $s = $segment;
				}
				$args = [
					$tr->getTranslation('matchName' . $e[0]),
					$data['data']['platformInfo']['platformUserHandle'],
					$s['stats']['tier']['metadata']['name'],
					$s['stats']['division']['metadata']['name'],
					$s['stats']['division']['metadata']['deltaDown'],
					$s['stats']['division']['metadata']['deltaUp'],
					$s['stats']['matchesPlayed']['value'],
					$s['stats']['winStreak']['value'],
					$s['stats']['rating']['value']
				];
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('matchesButton'), 'mt' . $e[1]);
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('overviewButton'), 'pi' . $e[1]);
				$t = $bot->text_link('&#8203;', $url) . $tr->getTranslation('playerMatchStats', $args);
			} else {
				$t = $tr->getTranslation('playerNotFound');
			}
			if ($v->query_id) {
				$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', false);
			} else {
				$bot->sendMessage($v->chat_id, $t, $buttons, 'def', false);
			}
			die;
		}
		if (!isset($v->query_data) && !isset($v->command) && isset($v->text)) {
			$data = $rl->getPlayer($v->text, $user['settings']['platform']);
			if ($data['data']) {
				$args = [
					$data['data']['platformInfo']['platformUserHandle'],
					$data['data']['segments'][0]['stats']['wins']['value'],
					$data['data']['segments'][0]['stats']['goals']['value'],
					$data['data']['segments'][0]['stats']['mVPs']['value'],
					$data['data']['segments'][0]['stats']['saves']['value'],
					$data['data']['segments'][0]['stats']['assists']['value'],
					$data['data']['segments'][0]['stats']['shots']['value'],
					round($data['data']['segments'][0]['stats']['goalShotRatio']['value']) . '%',
					round($data['data']['segments'][0]['stats']['seasonRewardLevel']['value']) . '%',
					$data['data']['segments'][0]['stats']['seasonRewardWins']['value'],
				];
				if (!is_null($data['data']['userInfo']['customAvatarUrl'])) {
					$url = $data['data']['userInfo']['customAvatarUrl'];
				} elseif (isset($data['data']['platformInfo']['avatarUrl'])) {
					$url = $data['data']['platformInfo']['avatarUrl'];
				} else {
					$url = 'https://telegra.ph/file/360f70097f25cdff092c4.jpg';
				}
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('matchesButton'), 'mt' . $v->text);
				$t = $bot->text_link('&#8203;', $url) . $tr->getTranslation('playerOverview', $args);
				$t .= PHP_EOL . PHP_EOL . $bot->italic($tr->getTranslation('lastUpdate', [date('D, j F Y \a\t H:i', strtotime($data['data']['metadata']['lastUpdated']['value'])) . ' UTC']));
			} else {
				$t = $tr->getTranslation('playerNotFound');
			}
			if ($v->query_id) {
				$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', false);
			} else {
				$bot->sendMessage($v->chat_id, $t, $buttons, 'def', false);
			}
		} else {
			$t = $tr->getTranslation('unknownCommand');
			if ($v->query_id) {
				$bot->answerCBQ($v->query_id, $t);
			} else {
				$bot->sendMessage($v->chat_id, $t);
			}
		}
	}
} 

# Unsupported chats (Auto-leave)
elseif (in_array($v->chat_type, ['group', 'supergroup', 'channels'])) {
	if ($v->chat_id != $rl->data_chat) $bot->leave($v->chat_id);
	die;
}

# Inline commands
if ($v->update['inline_query']) {
	$sw_text = $tr->getTranslation('helpInline');
	$sw_arg = 'inline'; // The message the bot receive is '/start inline'
	$results = [];
	# Search players with inline mode
	if ($v->query) {
		$e = explode(' ', $v->query, 2);
		if (!isset($e[1])) {
			$e[1] = $e[0];
			$e[0] = $user['settings']['platform'];
		}
		if ($e[0] != $user['settings']['platform'] && in_array($e[0], $rl->platforms)) {
			$user['settings']['platform'] = $e[0];
			$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $v->user_id]);
		}
		$data = $rl->getPlayer($e[1], $e[0]);
		if ($data['data']) {
			$args = [
				$data['data']['platformInfo']['platformUserHandle'],
				$data['data']['segments'][0]['stats']['wins']['value'],
				$data['data']['segments'][0]['stats']['goals']['value'],
				$data['data']['segments'][0]['stats']['mVPs']['value'],
				$data['data']['segments'][0]['stats']['saves']['value'],
				$data['data']['segments'][0]['stats']['assists']['value'],
				$data['data']['segments'][0]['stats']['shots']['value'],
				round($data['data']['segments'][0]['stats']['goalShotRatio']['value']) . '%',
				round($data['data']['segments'][0]['stats']['seasonRewardLevel']['value']) . '%',
				$data['data']['segments'][0]['stats']['seasonRewardWins']['value'],
			];
			if (!is_null($data['data']['userInfo']['customAvatarUrl'])) {
				$url = $data['data']['userInfo']['customAvatarUrl'];
			} elseif (isset($data['data']['platformInfo']['avatarUrl'])) {
				$url = $data['data']['platformInfo']['avatarUrl'];
			} else {
				$url = 'https://telegra.ph/file/360f70097f25cdff092c4.jpg';
			}
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('matchesButton'), 'mt' . $v->query);
			$t = $bot->text_link('&#8203;', $url) . $tr->getTranslation('playerOverview', $args);
			$t .= PHP_EOL . PHP_EOL . $bot->italic($tr->getTranslation('lastUpdate', [date('D, j F Y \a\t H:i', strtotime($data['data']['metadata']['lastUpdated']['value'])) . ' UTC']));
			$results[] = $bot->createInlineArticle(
				$v->query,
				$data['data']['platformInfo']['platformUserHandle'],
				$tr->getTranslation('platformSlug' . $data['data']['platformInfo']['platformSlug']),
				$bot->createTextInput($t, 'def', 0),
				$buttons,
				false,
				false,
				$url
			);
		} else {
			$sw_text = $tr->getTranslation('playerNotFound');
		}
	}
	$bot->answerIQ($v->id, $results, $sw_text, $sw_arg);
}

?>
