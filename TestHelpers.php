<?php


namespace AliasAPI\Tests;


use AliasAPI\CrudJson;
use AliasAPI\CrudTable;

/**
 * Class TestHelpers
 * Helpers and prepares data for tests
 *
 * @package AliasAPI\Tests
 */
class TestHelpers {

	/**
	 * Prepare request for create refund test
	 *
	 * @param string $tag
	 *
	 * @return array
	 */
	public static function createPurchaseRequest( $tag = 'create_purchase' ) {
		$request = array(
			'actionS' => TestParameters::ACTION_PURCHASE_CREATE,
			'ally'    => array(
				'alias'      => 'SandboxRest',
				'client'     => 'TestServer',
				'client_url' => TestParameters::CLIENT_URL,
				'server_url' => TestParameters::SERVER_URL,
				'user'       => 'UUID',
				'cart'       => 'promote-1',
			),
			'order'   => array(
				'amount'      => '10.00',
				'currency'    => 'USD',
				'description' => 'The Description is Real',
			),
			'tag'     => $tag,
		);

		return $request;
	}

	/**
	 * Call Alias API for make new purchase
	 *
	 * @param $http_client
	 * @param $request
	 * @param $key_pairs
	 *
	 * @return array
	 */
	public static function createPurchase( $http_client, $request ) {
		$key_pairs = array(
			'tag' => $request['tag'],
		);

		self::getResponse( $http_client, 'POST', $request );

		return CrudTable\read_rows( 'transactions', $key_pairs, false );
	}

	/**
	 * Prepare request for refund purchase test
	 *
	 * @param $tag
	 * @param $process_tag
	 *
	 * @return array
	 */
	public static function refundPurchaseRequest( $tag, $process_tag ) {
		$request = array(
			'actionS'     => TestParameters::ACTION_PURCHASE_REFUND,
			'ally'        => array(
				'alias'      => 'SandboxRest',
				'client'     => 'TestServer',
				'client_url' => TestParameters::CLIENT_URL,
				'server_url' => TestParameters::SERVER_URL,
				'user'       => 'UUID',
				'cart'       => 'promote-1',
			),
			'tag'         => $tag,
			'process_tag' => $process_tag,
		);

		return $request;
	}

	public static function refundPurchase( $http_client, $request ) {
		$key_pairs = array(
			'tag' => $request['tag'],
		);

		self::getResponse( $http_client, 'POST', $request );

		return CrudTable\read_rows( 'transactions', $key_pairs, false );
	}

	/**
	 * Prepare options before call request
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public static function prepareOptions( $request ) {
		$json_request = json_encode( $request, JSON_PRETTY_PRINT );

		$options = array(
			'headers' => array(
				'Content-Type'  => 'application/json; charset=UTF-8',
				'Cache-Control' => 'no-cache, must-revalidate',
			),
			'auth'    => array(
				'api_pass',
				TestParameters::AUTH_PASS,
			),
			'body'    => $json_request,
		);

		return $options;
	}

	/**
	 * Get Response from server
	 *
	 * @param $http_client
	 * @param $method
	 * @param $request
	 *
	 * @return array response answer
	 */
	public static function getResponse( $http_client, $method, $request ) {
		try {
			$options       = self::prepareOptions( $request );
			$response      = $http_client->request( $method, $request['ally']['server_url'], $options );
			$json_response = (string) $response->getBody();
		} catch ( Exception $ex ) {
			echo $ex->getResponse()->getBody();
			die();
		}

		try {
			$response_array = (array) json_decode( $json_response, true );
		} catch ( Exception $ex ) {
			echo $ex->getMessage();
			die();
		}

		return $response_array;
	}

	/**
	 * Prepare global variables for work dataase connection
	 */
	public static function prepareDatabaseConfigs() {
		$alias           = self::getAlias();
		$database_config = $alias['SandboxRest']['database_config'];

		if ( isset( $database_config['dsn'] ) ) {
			\defined( 'DB_DSN' ) || \define( 'DB_DSN', $database_config['dsn'] );
		}

		if ( isset( $database_config['username'] ) ) {
			\defined( 'DB_USERNAME' ) || \define( 'DB_USERNAME', $database_config['username'] );
		}

		if ( isset( $database_config['password'] ) ) {
			\defined( 'DB_PASSWORD' ) || \define( 'DB_PASSWORD', $database_config['password'] );
		}
	}

	/**
	 * Get alias config from .alias.json
	 *
	 * @return array
	 */
	public static function getAlias() {
		require_once( dirname( __FILE__ ) . '/../vendor/aliasapi/frame/crudjson/read_json_file.php' );

		return CrudJson\read_json_file( __DIR__ . '/../config/.alias.json' );
	}

	/**
	 * Clear file from jsondata folder
	 */
	public static function removeJsonFile( $tag ) {
		$tag_file = JSONPATH . '/' . $tag;
		if ( CrudJson\check_file_exists( $tag_file ) ) {
			CrudJson\delete_json_file( $tag_file );
		}
	}

}