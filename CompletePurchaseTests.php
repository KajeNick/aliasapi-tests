<?php

namespace AliasAPI\Tests;

use AliasAPI\Client;
use AliasAPI\CrudTable;
use GuzzleHttp;
use PHPUnit\Framework\TestCase;

class CompletePurchaseTests extends TestCase {

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
	 * Transactions for current tag
	 *
	 * @var array
	 */
	private $transactions;

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
		$this->tag         = 'complete_purchase';
	}

	/**
	 * Testing complete purchase
	 * Run 1: Get link for pay
	 * Run 2: Check completed purchase
	 */
	public function testCompletePurchase() {
		$this->key_pairs    = array(
			'tag'    => $this->tag,
			'status' => 'completed',
		);
		$this->transactions = CrudTable\read_rows( 'transactions', $this->key_pairs, false );

		if ( ! empty( $this->transactions ) ) {
			$this->assertIsArray( $this->transactions );
			$this->assertArrayHasKey( '0', $this->transactions );
			$this->assertEmpty( $this->transactions[0]['tokenauth'] );
			$this->assertEquals( 'completed', $this->transactions[0]['status'] );
			$this->assertNotEmpty( $this->transactions[0]['saleid'] );
			$this->assertEmpty( $this->transactions[0]['redirect_url'] );
		} else {
			$request      = TestHelpers::createPurchaseRequest( $this->tag );
			$transactions = TestHelpers::createPurchase( $this->http_client, $request );

			$this->assertIsArray( $transactions );
			$this->assertArrayHasKey( '0', $transactions );
			$this->assertNotEmpty( $transactions[0]['redirect_url'] );
			$this->assertEquals( 'created', $transactions[0]['status'] );

			echo 'For complete COMPLETE PURCHASE test, you must pay and restart this tests.' . PHP_EOL;
			echo 'Link for pay - ' . $transactions[0]['redirect_url'] . PHP_EOL;
		}
	}

	/**
	 * Clear all tested data
	 */
	protected function tearDown(): void {
		$this->http_client = null;
		if ( ! empty( $this->transactions ) ) {
			CrudTable\delete_rows( 'transactions', $this->key_pairs, 100 );
			TestHelpers::removeJsonFile( $this->tag );
		}
		$this->transactions = array();
	}
}