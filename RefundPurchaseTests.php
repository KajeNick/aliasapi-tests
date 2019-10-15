<?php

namespace AliasAPI\Tests;

use AliasAPI\Client;
use AliasAPI\CrudTable;
use GuzzleHttp;
use PHPUnit\Framework\TestCase;

class RefundPurchaseTests extends TestCase {

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
	 * Tag to refund row in DB
	 *
	 * @var string
	 */
	private $process_tag;

	/**
	 * Transactions for process tag
	 *
	 * @var array
	 */
	private $process_transactions;

	/**
	 * Transactions, maked after success refund
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
		$this->process_tag = 'refund_purchase_from';
		$this->tag         = 'refund_purchase_to';
	}

	/**
	 * Testing refund purchase
	 * Run 1: Get link for pay
	 * Run 2: Check completed refund purchase
	 */
	public function testRefundPurchase() {
		$this->key_pairs            = array(
			'tag'    => $this->process_tag,
			'status' => 'completed',
		);
		$this->process_transactions = CrudTable\read_rows( 'transactions', $this->key_pairs, false );

		if ( ! empty( $this->process_transactions ) ) {
			$request            = TestHelpers::refundPurchaseRequest( $this->tag, $this->process_tag );
			$this->transactions = TestHelpers::refundPurchase( $this->http_client, $request );

			$this->assertIsArray( $this->transactions );
			$this->assertArrayHasKey( '0', $this->transactions );

			$this->assertEquals( $this->transactions[0]['alias'], $this->process_transactions[0]['alias'] );
			$this->assertEquals( $this->transactions[0]['user'], $this->process_transactions[0]['user'] );
			$this->assertEquals( $this->transactions[0]['cart'], $this->process_transactions[0]['cart'] );
			$this->assertEquals( $this->transactions[0]['transactionid'], $this->process_transactions[0]['transactionid'] );
			$this->assertEquals( abs( $this->transactions[0]['saleid'] ), abs( $this->process_transactions[0]['saleid'] ) );
			$this->assertEquals( $this->transactions[0]['currency'], $this->process_transactions[0]['currency'] );
			$this->assertEquals( $this->transactions[0]['status'], $this->process_transactions[0]['status'] );
			$this->assertEquals( $this->transactions[0]['saleid'], $this->process_transactions[0]['saleid'] );

			$this->assertEquals( 'refund', $this->transactions[0]['type'] );
			$this->assertEmpty( $this->transactions[0]['tokenid'] );
			$this->assertNotEmpty( $this->transactions[0]['refundid'] );
			$this->assertEmpty( $this->transactions[0]['redirect_url'] );

		} else {
			$request      = TestHelpers::createPurchaseRequest( $this->process_tag );
			$transactions = TestHelpers::createPurchase( $this->http_client, $request );
			$this->assertIsArray( $transactions );
			$this->assertArrayHasKey( '0', $transactions );
			$this->assertNotEmpty( $transactions[0]['redirect_url'] );
			$this->assertEquals( 'created', $transactions[0]['status'] );

			echo 'For complete REFUND PURCHASE test, you must pay and restart this tests.' . PHP_EOL;
			echo 'Link for pay - ' . $transactions[0]['redirect_url'] . PHP_EOL;
		}
	}

	/**
	 * Clear all tested data
	 */
	protected function tearDown(): void {
		$this->http_client          = null;
		$this->process_transactions = array();

		if ( ! empty( $this->transactions ) ) {
			$this->transactions = array();

			CrudTable\delete_rows( 'transactions', $this->key_pairs, 100 );

			$this->key_pairs['tag'] = $this->tag;
			CrudTable\delete_rows( 'transactions', $this->key_pairs, 100 );

			TestHelpers::removeJsonFile( $this->process_tag );
			TestHelpers::removeJsonFile( $this->tag );
		}
	}
}