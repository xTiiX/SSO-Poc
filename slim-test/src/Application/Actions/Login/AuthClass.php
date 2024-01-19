<?php

use Firebase\JWT\JWT;
use GuzzleHttp\Client; // Similaire à cURL

class AuthController
{
	public function loginAction()
	{
		// on récupére les params du callback de keycloak
		$params = $this->params()->fromQuery();

		$AUTH_KEYCLOAK_URL = "https://auth.arda.wf/realms/stargate";

		$client = new Client();

		// Récupération de la public_key du keycloak
		try {
			$response = json_decode($client->get($AUTH_KEYCLOAK_URL)->getBody()->getContents(), true);
		} catch (\ClientException $e) {
		}

		if (!isset($response['public_key'])) {
			header("Location: /login_fail.html");
			die;
		}

		$keyPublic = "
-----BEGIN PUBLIC KEY-----
" . $response["public_key"] . "
-----END PUBLIC KEY-----
";

		// Récupération de la configuration du keycloak
		try {
			$response = json_decode($client->get($AUTH_KEYCLOAK_URL . "/.well-known/openid-configuration")->getBody()->getContents(), true);
		} catch (\ClientException $e) {
		}

		if (!isset($response['token_endpoint'])) {
			header("Location: /login_fail.html");
			die();
		}

		// Récupération du jwt_token depuis keycloak
		try {
			$response = json_decode($client->request("POST", $response['token_endpoint'], [
				'headers' => [
					"Content-type" => "application/x-www-form-urlencoded"
				],
				'form_params' => [
					"grant_type"    => "authorization_code",
					"client_id"    => 'dhd',
					"client_secret"    => 'tExrHGJqzdND1K9UoMyKJa5xx7M0cdFL',
					"code"     => $params['code'],
					"redirect_uri"     => 'XXXX', // Point entrée SI
				],
			])->getBody(), true);
		} catch (\ClientException $e) {
		}

		if (!isset($response['access_token'])) {
			header("Location: /login_fail.html");
			die();
		}

		try {
			$jwtToken = (array) JWT::decode($response['access_token'], $keyPublic, ['RS256']); // on accepte que le RS256 à vous de voir si on décide d'autre
		}
		catch(\Exception $exp) {
			$msError = $exp->getMessage();
			echo '<pre>';
			var_dump("ERR", $msError);
			echo '</pre>';die();
		}

		if (!isset($jwtToken["account_type"]) || $jwtToken["account_type"] != "collaborator" || !isset($jwtToken["preferred_username"])) {
			header("Location: /login_fail.html");
			die();
		}

		// Authentification OK
		header("Location: /login_success.html");
		die();
	}
}