<?php

namespace AliasAPI\Tests;

use AliasAPI\Client;
use AliasAPI\CrudTable;
use GuzzleHttp;
use PHPUnit\Framework\TestCase;

class CreatePurchaseTests extends TestCase {

	/**
	 * Client for work http
	 *
	 * @var GuzzleHttp\Client
	 */
	private $http_client;

	/**
	 * Key pairs for database
	 *
	 * @var array
	 */
	private $key_pairs;

	/**
	 * Current test tag for separate tests in DB
	 *
	 * @var string
	 */
	private $tag;

	/**
	 * Prepare all work data for test
	 */
	protected function setUp(): void {
		require_once( dirname( __FILE__ ) . '/../vendor/aliasapi/frame/client/create_client.php' );
		require_once( dirname( __FILE__ ) . '/TestHelpers.php' );
		require_once( dirname( __FILE__ ) . '/TestParameters.php' );

		TestHelpers::prepareDatabaseConfigs();

		Client\create_client( 'money' );
		$this->http_client = new GuzzleHttp\Client( [ 'base_uri' => 'http://money/' ] );
		$this->tag         = 'create_purchase';
	}

	/**
	 * Create purchase
	 */
	public function testCreatePurchase() {
		$request         = TestHelpers::createPurchaseRequest( $this->tag );
		$this->key_pairs = array(
			'tag' => $request['tag'],
		);
		$transactions    = TestHelpers::createPurchase( $this->http_client, $request, $this->key_pairs );

		$this->assertIsArray( $transactions );
		$this->assertArrayHasKey( '0', $transactions );
		$this->assertEquals( $request['tag'], $transactions[0]['tag'] );
		$this->assertEquals( 'sale', $transactions[0]['type'] );
		$this->assertNotEmpty( $transactions[0]['tokenauth'] );
		$this->assertNotEmpty( $transactions[0]['transactionid'] );
		$this->assertNotEmpty( $transactions[0]['amount'] );
		$this->assertNotEmpty( $transactions[0]['currency'] );
		$this->assertEquals( 'created', $transactions[0]['status'] );
		$this->assertNotEmpty( $transactions[0]['tokenid'] );
		$this->assertNotEmpty( $transactions[0]['redirect_url'] );
	}

	/**
	 * Clear all tested data
	 */
	protected function tearDown(): void {
		$this->http_client = null;
		CrudTable\delete_rows( 'transactions', $this->key_pairs, 100 );
		TestHelpers::removeJsonFile( $this->tag );
	}

}