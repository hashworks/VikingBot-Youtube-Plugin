<?php

class youtubePlugin extends basePlugin {

	private $disabled;

	/**
	 * Called when plugins are loaded
	 *
	 * @param mixed[]	$config
	 * @param resource 	$socket
	**/
	public function __construct($config, $socket) {
		parent::__construct($config, $socket);
		$this->disabled = false;
		if (!ini_get('allow_url_fopen')) {
			try {
				ini_set('allow_url_fopen', '1');
			} catch (Exception $e) {
				logMsg("Unable to enable allow_url_fopen, disabling youtubePlugin.");
				$this->disabled = true;
			}
		}
	}

	/**
	 * Called when messages are posted on the channel
	 * the bot are in, or when somebody talks to it
	 *
	 * @param string $from
	 * @param string $channel
	 * @param string $msg
	 */
	public function onMessage($from, $channel, $msg) {
		if ($this->disabled === true) {
			return;
		}
		preg_match_all("/http[s]{0,1}:\/\/(?:www\.){0,1}youtu(?:\.be\/[-a-z0-9]+|be\.com\/watch[?&=-a-z0-9]+)/i", $msg, $matches);
		$matches = array_splice($matches[0], 0, 5);
		foreach ($matches as $link) {
			$title = $this->getYoutubeTitle($link);
			if ($title !== false) {
				$this->sendMessage($channel, "[YOUTUBE] " . $title);
			}
		}
	}

	private function getYoutubeTitle($link) {
		$data = @file_get_contents('http://www.youtube.com/oembed?url=' . $link . '&format=json');
		if (!empty($data) && ($data = json_decode($data, true)) !== NULL) {
			if (isset($data['title']) && !empty($data['title'])) {
				return $data['title'];
			}
		}
		return false;
	}

	/**
	 * @param string $to
	 * @param string $msg
	 * @param string|array $highlight = NULL
	 */
	private function sendMessage($to, $msg, $highlight = NULL) {
		if ($highlight !== NULL) {
			if (is_array($highlight)) {
				$highlight = join(", ", $highlight);
			}
			$msg = $highlight . ": " . $msg;
		}
		sendMessage($this->socket, $to, $msg);
	}
}