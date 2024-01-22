<?php

require 'vendor/autoload.php'; // Assurez-vous d'avoir la bibliothèque firebase/php-jwt installée via Composer

use Firebase\JWT\JWT;

$env = parse_ini_file('.env');

// Configuration Keycloak
$keycloakConfig = [
	'clientId'                => $env['CLIENT_ID'],
	'clientSecret'            => $env['CLIENT_SECRET'],
	'redirectUri'             => 'http://localhost:8080/login.php',
	'authorizationEndpoint'   => 'https://auth.arda.wf/realms/stargate/protocol/openid-connect/auth',
	'tokenEndpoint'           => 'https://auth.arda.wf/realms/stargate/protocol/openid-connect/token',
	'tokenKeysEndpoint'		  => 'https://auth.arda.wf/realms/stargate/protocol/openid-connect/certs',
];

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
	try {
		// Si nous avons le code, échangez-le contre un jeton d'accès
		$tokenResponse = getToken($_GET['code']);
		$idToken = $tokenResponse['access_token'];

		// Utilisation de l'accessToken pour avoir les données
		$jwt_decode = JWT::decode($idToken, new \Firebase\JWT\Key(file_get_contents('public.pem'), 'RS256'));

		// Affichez les informations de l'utilisateur
		error_log('- Response JWT Decode -');
		error_log(print_r($jwt_decode, true));
		echo 'Hello, ' . print_r($jwt_decode, true) . '!';
	} catch (Exception $e) {
		// Gestion des erreurs d'authentification
		echo 'Erreur d\'authentification: ' . $e->getMessage();
	}
}

// Fonction pour échanger le code contre un jeton d'accès
function getToken($code)
{
	global $keycloakConfig;

	$tokenEndpoint = $keycloakConfig['tokenEndpoint'];

	$data = [
		'grant_type'    => 'authorization_code',
		'code'          => $code,
		'redirect_uri'  => $keycloakConfig['redirectUri'],
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