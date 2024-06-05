<?php

require 'vendor/autoload.php'; // Assurez-vous d'avoir la bibliothèque firebase/php-jwt installée via Composer

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

$env = parse_ini_file('.env');

// Recuperation de la configuration de Keycloak
if (!$env['WELL_KNOWN']) {
	error_log('////////// ERROR : .env configuration isn\'t good /////////');
	echo '////////// ERROR : .env configuration isn\'t good /////////';
	die;
}

$options = [
	'http' => [
		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		'method'  => 'GET',
		'content' => [],
	],
];

$context  = stream_context_create($options);
$response = file_get_contents($env['WELL_KNOWN'], false, $context);

if (!$response) {
	error_log('////////// ERROR : call to keycloak isn\'t working /////////');
	echo '////////// ERROR : call to keycloak isn\'t working /////////';
	die;
}

$response = json_decode($response);
//error_log(print_r($response, true));

// Configuration Keycloak
$keycloakConfig = [
	'clientId'                => $env['CLIENT_ID'],
	'clientSecret'            => $env['CLIENT_SECRET'],
	'redirectUri'             => full_url($_SERVER),
	'authorizationEndpoint'   => $response->authorization_endpoint,
	'tokenEndpoint'           => $response->token_endpoint,
	'tokenKeysEndpoint'		  => $response->jwks_uri,
];

// error_log('redirect URI : '. print_r('http://localhost:8080/login.php', true));
// error_log('redirect URI : '. print_r($keycloakConfig['redirectUri'], true));

// Vérifier si nous avons le code d'authentification
if (!isset($_GET['code'])) {
	// Si non, rediriger vers l'URL d'autorisation
	$authUrl = $keycloakConfig['authorizationEndpoint'] . '?' . http_build_query([
			'client_id'     => $keycloakConfig['clientId'],
			'redirect_uri'  => $keycloakConfig['redirectUri'],
			'response_type' => 'code',
			'scope'         => 'openid email profile multipass products', // Les scopes que vous souhaitez demander
		]);
	header('Location: ' . $authUrl);
	exit;
} else {
	// Si nous avons le code, échangez-le contre un id_token
	$tokenResponse = getToken($_GET['code']);
	$idToken = $tokenResponse['id_token'];
	try {
		// Utilisation de l'idToken pour avoir les données
		$jwt_id_decode = JWT::decode($idToken, new \Firebase\JWT\Key(file_get_contents('public.pem', true), 'RS256'));

		// Affichez les informations de l'utilisateur
		error_log('- Response JWT ID Token Decode -');
		error_log(print_r($jwt_id_decode, true));
		echo 'Hello, ' . print_r($jwt_id_decode, true) . '!';
	} catch (Exception $e) {
		// Not working with public.pem -> Test with certs
		error_log('Try with certs');
		try {
			// Recuperation de la cle public pour la traduction du JWT
			$certs = getCerts()['keys'];
			$keys = JWK::parseKeySet(['keys' => $certs]);
			$rs_key = null;
			foreach ($keys as $key) {
				if ($key->getAlgorithm() === 'RS256') {
					$rs_key = $key;
				}
			}
			$opensslKey = $rs_key->getKeyMaterial();
			$pem = openssl_pkey_get_details($opensslKey)['key'];

			$jwt_id_decode = JWT::decode($idToken, new \Firebase\JWT\Key($pem, 'RS256'));

			// Si jwt decode qui fonctionne -> Enregistrement de la clé publique dans le fichier
			error_log('-> Worked : New key saved in public.pem');
			file_put_contents('public.pem', $pem);

			echo 'Hello, ' . print_r($jwt_id_decode, true) . '!';
		} catch (Exception $e) {
			error_log('//////////////////ERROR/////////////');
			// Gestion des erreurs d'authentification
			echo 'Erreur d\'authentification: ' . $e->getMessage();
		}
	}
}

// Fonction pour échanger le code contre un id_token
function getToken($code)
{
	global $keycloakConfig;

	$tokenEndpoint = $keycloakConfig['tokenEndpoint'];

	$data = [
		'grant_type'    => 'authorization_code',
		'code'          => $code,
		'redirect_uri'  => 'http://localhost:8080/login.php',
		'client_id'     => $keycloakConfig['clientId'],
		'client_secret' => $keycloakConfig['clientSecret'],
	];

	$options = [
		'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
		],
	];

	$context  = stream_context_create($options);
	$response = file_get_contents($tokenEndpoint, false, $context);

	error_log('- Response POST -');
	error_log(print_r($response, true));

	return json_decode($response, true);
}

// Fonction pour recupérer les certificats de openidconnect
function getCerts()
{
	global $keycloakConfig;

	$tokenKeysEndpoint = $keycloakConfig['tokenKeysEndpoint'];

	$options = [
		'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'GET',
			'content' => [],
		],
	];

	$context  = stream_context_create($options);
	$response = file_get_contents($tokenKeysEndpoint, false, $context);

	error_log('- Response Certs -');
	error_log(print_r($response, true));

	return json_decode($response, true);
}

// fonction permettant la reconstruction de l'URL actuellement appelée
function url_origin($s): string
{
	$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
	$sp       = strtolower( $s['SERVER_PROTOCOL'] );
	$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
	$port     = $s['SERVER_PORT'];
	$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
	$host     = ($s['HTTP_X_FORWARDED_HOST'] ?? ($s['HTTP_HOST'] ?? null));
	$host     = $host ?? $s['SERVER_NAME'] . $port;
	return $protocol . '://' . $host;
}

// fonction permettant la reconstruction de l'URL actuellement appelée avec la request_uri
function full_url($s): string
{
	return url_origin($s) . $s['REQUEST_URI'];
}
