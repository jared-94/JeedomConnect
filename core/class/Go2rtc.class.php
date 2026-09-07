<?php

/* * ***************************Includes********************************* */

require_once __DIR__ . '/JeedomConnectWidget.class.php';
require_once __DIR__ . '/JeedomConnectLogs.class.php';
require_once __DIR__ . '/JeedomConnectLock.class.php';

/**
 * Pont go2rtc (https://github.com/AlexxIT/go2rtc) -> WebRTC pour les widgets
 * caméra. Daemon géré par le plugin, comme JeedomConnectd.py, mais AVEC SES
 * PROPRES méthodes start()/stop()/info() : les noms deamon_start/stop/info
 * sont réservés par Jeedom pour le démon principal du plugin (JeedomConnect
 * n'en a qu'un via cette convention), donc un second démon ne peut pas s'y
 * accrocher automatiquement. Pour cette v1 (POC), le démon est démarré
 * paresseusement dès qu'un widget caméra avec webrtcEnabled est enregistré
 * (registerStream()) plutôt que via un toggle dédié sur la page de config.
 *
 * En LAN, l'app se connecte en direct à go2rtc (WebSocket vers /api/ws) ;
 * go2rtc lui-même n'est pas exposé hors LAN, aucune authentification n'est
 * configurée sur son API (même niveau d'exposition que le flux RTSP direct
 * utilisé aujourd'hui par VLCPlayer).
 *
 * Hors LAN, le signaling passe par un second petit processus géré par cette
 * classe : webrtcBridge.py (localhost uniquement). Il traduit une série
 * d'appels HTTP courts (offer/candidate/poll, voir apiHelper::webrtcOffer
 * et consorts) en une connexion WebSocket vers go2rtc/api/ws, ce qui permet
 * un vrai trickle ICE (candidats envoyés au fil de l'eau, comme le client de
 * référence de go2rtc) sans exiger de canal WebSocket bout-en-bout côté app
 * - une première version utilisait le canal WS du démon JeedomConnectd.py,
 * abandonnée car elle ne fonctionne que si l'utilisateur a activé l'option
 * optionnelle useWs (minoritaire), alors que le HTTP fonctionne pour tous
 * les utilisateurs, avec ou sans reverse proxy personnalisé (voir plan
 * "Passage en signaling WebSocket + trickle ICE" pour l'historique complet).
 *
 * Côté app : le lecteur WebRTC s'exécute dans une WebView (moteur Chromium),
 * pas via react-native-webrtc - voir webrtcPlayer.js pour le pourquoi
 * (limitation documentée et non résolue de la pile ICE native embarquée par
 * react-native-webrtc sur réseau cellulaire, cf. historique de session).
 */
class Go2rtc {

	// Version épinglée (asset filenames confirmés via l'API GitHub au moment
	// de l'écriture). Vérifier https://github.com/AlexxIT/go2rtc/releases si
	// une mise à jour est nécessaire un jour.
	const GO2RTC_TAG = 'v1.9.14';

	public static $_bin_dir = __DIR__ . '/../../resources/go2rtc/';

	public static function getBinaryPath() {
		return self::$_bin_dir . 'go2rtc';
	}

	public static function getConfigPath() {
		return self::$_bin_dir . 'go2rtc.yaml';
	}

	public static function getPidFile() {
		return jeedom::getTmpFolder(__CLASS__) . '/go2rtc.pid';
	}

	public static function getPort() {
		return intval(config::byKey('go2rtcPort', 'JeedomConnect', 1984));
	}

	public static function getLocalApiUrl() {
		return 'http://127.0.0.1:' . self::getPort();
	}

	public static function streamName($widgetId) {
		return 'jc_' . $widgetId;
	}

	/*     * ********************** WEBRTC BRIDGE (HTTP -> go2rtc/api/ws) ******** */

	// Même venv Python que le démon principal (resources/requirements.txt y
	// inclut websocket-client, nécessaire à webrtcBridge.py) - installé via le
	// même flux "réinstaller les dépendances" que JeedomConnectd.py, pas de
	// nouvelle étape d'installation pour l'utilisateur.
	private static function getPythonPath() {
		return __DIR__ . '/../../resources/venv/bin/python3';
	}

	private static function getBridgeScriptPath() {
		return __DIR__ . '/../../resources/webrtcBridge.py';
	}

	public static function getBridgePort() {
		return intval(config::byKey('go2rtcBridgePort', 'JeedomConnect', 1985));
	}

	private static function getBridgeLocalUrl() {
		return 'http://127.0.0.1:' . self::getBridgePort();
	}

	private static function getBridgePidFile() {
		return jeedom::getTmpFolder(__CLASS__) . '/webrtc_bridge.pid';
	}

	/*     * ********************** INSTALL / BINARY *************************** */

	public static function isInstalled() {
		$bin = self::getBinaryPath();
		if (!file_exists($bin)) {
			return false;
		}
		if (!is_executable($bin)) {
			// Le bit exécutable peut être perdu après coup (ex. une opération
			// externe qui réinitialise les droits du dossier plugin) sans que le
			// binaire lui-même soit corrompu - se contenter d'un chmod plutôt que
			// de re-télécharger 5+ Mo inutilement.
			@chmod($bin, 0755);
			clearstatcache(true, $bin);
		}
		return is_executable($bin);
	}

	private static function getBinaryAsset() {
		switch (php_uname('m')) {
			case 'x86_64':
				return 'go2rtc_linux_amd64';
			case 'aarch64':
				return 'go2rtc_linux_arm64';
			case 'armv7l':
				return 'go2rtc_linux_arm';
			default:
				throw new Exception(__('Architecture non supportée pour go2rtc : ', __FILE__) . php_uname('m'));
		}
	}

	public static function install() {
		if (!is_dir(self::$_bin_dir)) {
			mkdir(self::$_bin_dir, 0755, true);
		}

		$filename = self::getBinaryAsset();
		JCLog::debug('go2rtc install - asset : ' . $filename);

		$sh_path = realpath(__DIR__ . '/../../resources/installGo2rtc.sh');
		// Invoqué via `sh <script>` plutôt qu'en exécution directe : ne
		// nécessite que la permission de lecture sur le script, pas le bit
		// exécutable - évite un "Permission denied" selon comment/par qui le
		// fichier a été déposé sur le serveur (chmod échoue silencieusement si
		// le process PHP n'est pas propriétaire du fichier).
		$cmd = 'sh ' . escapeshellarg($sh_path) . ' ' . escapeshellarg(self::GO2RTC_TAG)
			. ' ' . escapeshellarg($filename) . ' ' . escapeshellarg(self::getBinaryPath())
			. ' >> ' . log::getPathToLog('JeedomConnect_go2rtc') . ' 2>&1';
		JCLog::debug('go2rtc install cmd : ' . $cmd);
		shell_exec($cmd);

		if (!self::isInstalled()) {
			throw new Exception(__("Impossible de télécharger le binaire go2rtc, vérifiez le log", __FILE__));
		}
	}

	/*     * ********************** DAEMON MANAGEMENT *************************** */

	public static function info() {
		$return = array();
		$return['log'] = 'JeedomConnect_go2rtc';
		$return['state'] = 'nok';

		$pid_file = self::getPidFile();
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				@unlink($pid_file);
			}
		}
		return $return;
	}

	private static function writeConfig() {
		if (!is_dir(self::$_bin_dir)) {
			mkdir(self::$_bin_dir, 0755, true);
		}
		// streams vide au démarrage : les caméras sont enregistrées
		// dynamiquement via l'API HTTP de go2rtc (registerStream()), qui les
		// persiste elle-même dans ce fichier (PUT /api/streams écrit sa
		// propre config) - pas besoin de les lister ici.
		//
		// allow_paths restreint l'API exposée au strict nécessaire :
		// /api/streams (utilisé uniquement en local par ce plugin PHP pour
		// enregistrer les flux) et /api/ws (signaling WebRTC en trickle ICE -
		// accès direct en LAN depuis la WebView, ou via webrtcBridge.py en
		// local hors LAN, voir Go2rtc::openWebrtcSession). Ferme /api/webrtc
		// (échange SDP figé, plus utilisé), /api/config (lecture/écriture de
		// toute la config sans authentification), /api/restart, /api/exit et
		// l'UI web statique.
		// Attention : allow_paths ne distingue pas l'appelant (local vs LAN)
		// - /api/streams reste donc atteignable depuis le LAN et expose les
		// URLs sources (identifiants RTSP inclus le cas échéant) ; fermer
		// complètement ce point nécessiterait une authentification
		// (volontairement hors scope de cette passe, voir plan go2rtc).
		// webrtc.candidates: "stun:8555" - fixe le port UDP/TCP média sur 8555
		// (déjà la valeur par défaut de go2rtc) et annonce l'adresse publique
		// découverte par STUN SUR CE PORT PRÉCIS, plutôt que de laisser go2rtc
		// annoncer le port éphémère attribué à chaque nouvelle requête STUN.
		// Utile seulement si l'utilisateur redirige un jour ce port sur sa box
		// (non requis pour l'usage courant LAN + pont HTTP hors LAN).
		$yaml = "api:\n"
			. "  listen: \":" . self::getPort() . "\"\n"
			. "  allow_paths:\n"
			. "    - /api/streams\n"
			. "    - /api/ws\n"
			. "webrtc:\n"
			. "  candidates:\n"
			. "    - stun:8555\n"
			. "streams:\n";
		file_put_contents(self::getConfigPath(), $yaml);
	}

	public static function start() {
		if (!self::isInstalled()) {
			throw new Exception(__("go2rtc n'est pas installé. Veuillez réinstaller les dépendances", __FILE__));
		}

		self::stop();
		JCLog::info('Starting go2rtc daemon');

		// Ne réécrit le fichier de config que s'il n'existe pas déjà : un
		// redémarrage (ex. après un simple restart du plugin) ne doit pas
		// effacer les streams que go2rtc y a lui-même persistés via ses PUT
		// précédents (voir writeConfig()).
		if (!file_exists(self::getConfigPath())) {
			self::writeConfig();
		}

		$cmd = escapeshellarg(self::getBinaryPath()) . ' -config ' . escapeshellarg(self::getConfigPath());
		$pidFile = self::getPidFile();
		exec($cmd . ' >> ' . log::getPathToLog('JeedomConnect_go2rtc') . ' 2>&1 & echo $! > ' . escapeshellarg($pidFile));

		$i = 0;
		while ($i < 10) {
			$info = self::info();
			if ($info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 10) {
			log::add('JeedomConnect', 'error', __('Impossible de démarrer go2rtc, vérifiez le log', __FILE__), 'unableStartGo2rtc');
			return false;
		}
		message::removeAll('JeedomConnect', 'unableStartGo2rtc');
		return true;
	}

	public static function stop() {
		JCLog::info('Stopping go2rtc daemon');
		$pid_file = self::getPidFile();
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
			@unlink($pid_file);
		}
		system::kill('go2rtc -config');
		self::stopBridge();
		sleep(1);
	}

	private static function ensureStarted() {
		// Deux widgets caméra enregistrés à quelques instants d'intervalle
		// peuvent chacun déclencher leur propre saveConfig() -> registerStream()
		// -> ensureStarted() dans des requêtes PHP concurrentes : sans verrou,
		// les deux peuvent voir isInstalled()==false en même temps et lancer
		// chacun leur propre téléchargement/installation en parallèle (écritures
		// concurrentes sur le même fichier binaire, risque d'échec transitoire).
		$lock = new JeedomConnectLock('Go2rtc_ensureStarted');
		try {
			if (!$lock->Lock()) {
				JCLog::warning('go2rtc: verrou ensureStarted non obtenu - une autre requête le démarre probablement déjà');
				return;
			}
			if (!self::isInstalled()) {
				self::install();
			}
			$info = self::info();
			if ($info['state'] != 'ok') {
				self::start();
			}
		} finally {
			unset($lock);
		}
	}

	/*     * ********************** STREAM REGISTRATION *************************** */

	private static function curlRequest($url, $method) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_exec($ch);
		if ($error = curl_error($ch)) {
			JCLog::warning('go2rtc API error (' . $method . ' ' . $url . ') => ' . $error);
		}
		curl_close($ch);
	}

	private static function bridgeInfo() {
		$return = array();
		$return['state'] = 'nok';
		$pid_file = self::getBridgePidFile();
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				@unlink($pid_file);
			}
		}
		return $return;
	}

	private static function startBridge() {
		if (!file_exists(self::getPythonPath())) {
			throw new Exception(__("VENV n'est pas disponible. Veuillez réinstaller les dépendances", __FILE__));
		}

		self::stopBridge();
		JCLog::info('Starting webrtc bridge');

		$cmd = escapeshellarg(self::getPythonPath()) . ' ' . escapeshellarg(self::getBridgeScriptPath())
			. ' --port ' . self::getBridgePort()
			. ' --go2rtcport ' . self::getPort()
			. ' --pid ' . escapeshellarg(self::getBridgePidFile());
		exec($cmd . ' >> ' . log::getPathToLog('JeedomConnect_webrtc_bridge') . ' 2>&1 &');

		$i = 0;
		while ($i < 10) {
			if (self::bridgeInfo()['state'] == 'ok') {
				break;
			}
			usleep(200000);
			$i++;
		}
		if ($i >= 10) {
			log::add('JeedomConnect', 'error', __('Impossible de démarrer le pont WebRTC, vérifiez le log', __FILE__), 'unableStartWebrtcBridge');
			return false;
		}
		message::removeAll('JeedomConnect', 'unableStartWebrtcBridge');
		return true;
	}

	private static function stopBridge() {
		$pid_file = self::getBridgePidFile();
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
			@unlink($pid_file);
		}
		system::kill('webrtcBridge.py');
	}

	/**
	 * Un process Python déjà lancé ne recharge jamais son propre fichier -
	 * si webrtcBridge.py a été modifié depuis (mise à jour du plugin, ou
	 * itération de dev) après le démarrage du process actuel, il tourne
	 * avec l'ancien code sans qu'aucun signe extérieur ne le montre (jusqu'à
	 * ce qu'une route ait changé et réponde 404, par ex.). Comparer la date
	 * du script à celle du pidfile (écrit par le script à son démarrage)
	 * permet de détecter ce décalage et de forcer un redémarrage tout seul,
	 * sans action manuelle.
	 */
	private static function isBridgeStale() {
		$pidFile = self::getBridgePidFile();
		$script = self::getBridgeScriptPath();
		if (!file_exists($pidFile) || !file_exists($script)) {
			return false;
		}
		return filemtime($script) > filemtime($pidFile);
	}

	/**
	 * Démarre go2rtc ET le pont HTTP, nécessaires tous les deux pour le
	 * signaling hors LAN (voir apiHelper::cameraStreamOpen et consorts).
	 */
	private static function ensureBridgeStarted() {
		self::ensureStarted();
		if (self::bridgeInfo()['state'] == 'ok' && self::isBridgeStale()) {
			JCLog::info('webrtc bridge: script modifié depuis le dernier démarrage - redémarrage');
			self::stopBridge();
		}
		if (self::bridgeInfo()['state'] != 'ok') {
			if (!self::startBridge()) {
				throw new Exception(__("Impossible de démarrer le pont WebRTC", __FILE__));
			}
		}
	}

	private static function bridgeCurl($path, $query = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::getBridgeLocalUrl() . $path . ($query ? '?' . $query : ''));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		// Généreux : /open attend que le pont ait fini de se connecter à
		// go2rtc (jusqu'à 10s côté Python, cf. webrtcBridge.py) - laisser de
		// la marge pour ne pas couper juste avant que ça réponde.
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		return $ch;
	}

	/**
	 * Ouvre une session vers go2rtc/api/ws pour un widget caméra, pour le
	 * compte de l'app quand elle n'est pas sur le LAN (voir
	 * apiHelper::cameraStreamOpen - méthode JSON-RPC "CAMERA_STREAM_OPEN").
	 * En LAN, l'app se connecte en direct à go2rtc (voir webrtcPlayer.js).
	 * Générique : $message est le premier message envoyé à go2rtc une fois
	 * la session ouverte - {type:"webrtc/offer",...} pour le signaling
	 * WebRTC, {type:"mse",...} pour démarrer un flux vidéo MSE (voir
	 * webrtcPlayer.js pour le détail des deux usages).
	 *
	 * @return string l'identifiant de session à réutiliser pour
	 *                 sendToSession()/pollSession()/closeSession()
	 * @throws Exception si go2rtc ou le pont sont injoignables
	 */
	public static function openSession($widgetId, $message) {
		self::ensureBridgeStarted();

		$ch = self::bridgeCurl('/open', 'widgetId=' . rawurlencode($widgetId));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('message' => $message)));
		$body = curl_exec($ch);
		$error = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($error) {
			throw new Exception('Erreur réseau vers le pont WebRTC : ' . $error);
		}
		$decoded = json_decode($body, true);
		if ($httpCode != 200 || !is_array($decoded) || empty($decoded['sessionId'])) {
			throw new Exception('Pont WebRTC : réponse invalide (' . $httpCode . ') : ' . $body);
		}
		return $decoded['sessionId'];
	}

	public static function sendToSession($sessionId, $message) {
		$ch = self::bridgeCurl('/send', 'sessionId=' . rawurlencode($sessionId));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('message' => $message)));
		curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * @return array la liste des messages reçus de go2rtc depuis le dernier
	 *                appel, chacun sous la forme {kind:"json", data:{...}}
	 *                ou {kind:"binary", data:"<base64>"} (fragments MSE).
	 */
	public static function pollSession($sessionId) {
		$ch = self::bridgeCurl('/poll', 'sessionId=' . rawurlencode($sessionId));
		$body = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$decoded = json_decode($body, true);
		if ($httpCode != 200 || !is_array($decoded)) {
			throw new Exception('Pont WebRTC : session introuvable ou expirée');
		}
		return $decoded['messages'] ?? array();
	}

	public static function closeSession($sessionId) {
		$ch = self::bridgeCurl('/close', 'sessionId=' . rawurlencode($sessionId));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * Enregistre (ou désenregistre) le flux d'un widget caméra auprès de
	 * go2rtc, à appeler depuis JeedomConnectWidget::updateWidgetConfig()
	 * chaque fois qu'un widget de type "camera" est sauvegardé.
	 *
	 * @param string|int $widgetId
	 * @param array $conf configuration complète du widget (type, streamUrl,
	 *                     streamUrlInfo, username, password, webrtcEnabled...)
	 */
	public static function registerStream($widgetId, $conf) {
		if (empty($conf['webrtcEnabled'])) {
			self::unregisterStream($widgetId);
			return;
		}

		$url = JeedomConnectWidget::resolveConfUrl($conf, 'streamUrl', 'streamUrlInfo');
		if (!is_string($url) || $url == '') {
			JCLog::warning('go2rtc: pas de streamUrl pour le widget ' . $widgetId . ' - webrtcEnabled ignoré');
			return;
		}

		$replaceArr = array(
			'#username#' => rawurlencode($conf['username'] ?? ''),
			'#password#' => rawurlencode($conf['password'] ?? ''),
		);
		$url = str_replace(array_keys($replaceArr), $replaceArr, $url);

		try {
			self::ensureStarted();
		} catch (Exception $e) {
			JCLog::error('go2rtc: impossible de démarrer le démon - ' . $e->getMessage());
			return;
		}

		$apiUrl = self::getLocalApiUrl() . '/api/streams?name=' . rawurlencode(self::streamName($widgetId))
			. '&src=' . rawurlencode($url);
		self::curlRequest($apiUrl, 'PUT');
	}

	public static function unregisterStream($widgetId) {
		if (!self::isInstalled()) {
			return;
		}
		$info = self::info();
		if ($info['state'] != 'ok') {
			return;
		}
		$apiUrl = self::getLocalApiUrl() . '/api/streams?src=' . rawurlencode(self::streamName($widgetId));
		self::curlRequest($apiUrl, 'DELETE');
	}
}
