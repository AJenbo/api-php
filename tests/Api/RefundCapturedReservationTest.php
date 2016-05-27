<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Altapay\ApiTest\Api;

use Altapay\Api\Request\OrderLine;
use Altapay\Api\Response\Refund;
use Altapay\Api\Response\Transaction;
use Altapay\Api\Exceptions\AuthenticationRequiredException;
use Altapay\Api\Exceptions\ClientException;
use Altapay\Api\RefundCapturedReservation;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class RefundCapturedReservationTest extends AbstractApiTest
{

    /**
     * @return RefundCapturedReservation
     */
    protected function getRefundCaptureReservation()
    {
        $client = $this->getClient($mock = new MockHandler([
            new Response(200, ['text-content' => 'application/xml'], file_get_contents(__DIR__ . '/Results/refundcapture.xml'))
        ]));

        return (new RefundCapturedReservation())
            ->setClient($client)
            ->setAuthentication($this->getAuth())
            ;
    }

    public function test_refund_reservation()
    {
        $api = $this->getRefundCaptureReservation();
        $api->setTransactionId(123);
        $this->assertInstanceOf(Refund::class, $api->call());
    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_data()
    {
        $api = $this->getRefundCaptureReservation();
        $api->setTransactionId(123);
        /** @var Refund $response */
        $response = $api->call();

        $this->assertEquals(0.12, $response->RefundAmount);
        $this->assertEquals('978', $response->RefundCurrency);
        $this->assertEquals('Success', $response->Result);
        $this->assertEquals('Success', $response->RefundResult);
        $this->assertCount(1, $response->Transactions);
    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_transactions_data()
    {
        $api = $this->getRefundCaptureReservation();
        $api->setTransactionId(123);
        /** @var Refund $response */
        $response = $api->call();
        /** @var Transaction $transaction */
        $transaction = $response->Transactions[0];
        $this->assertEquals(1, $transaction->TransactionId);
        $this->assertEquals(978, $transaction->MerchantCurrency);
        $this->assertEquals(13.37, $transaction->FraudRiskScore);
        $this->assertEquals(1, $transaction->ReservedAmount);
    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_transaction_request()
    {
        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $api = $this->getRefundCaptureReservation();
        $api->setTransaction($transaction);
        $api->setAmount(158);
        $api->setReconciliationIdentifier('myidentifier');
        $api->setInvoiceNumber('number');
        $api->setAllowOverRefund(true);
        $api->call();

        $request = $api->getRawRequest();

        $this->assertEquals($this->getExceptedUri('refundCapturedReservation/'), $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $parts);
        $this->assertEquals(456, $parts['transaction_id']);
        $this->assertEquals(158, $parts['amount']);
        $this->assertEquals('myidentifier', $parts['reconciliation_identifier']);
        $this->assertEquals('number', $parts['invoice_number']);
        $this->assertEquals('1', $parts['allow_over_refund']);

    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_transaction_orderlines()
    {
        $orderlines = [];
        $orderline = new OrderLine('White sugar', 'productid', 1.5, 5.75);
        $orderline->taxPercent = 20;
        $orderline->unitCode = 'kg';
        $orderlines[] = $orderline;

        $orderline = new OrderLine('Brown sugar', 'productid2', 2.5, 8.75);
        $orderline->unitCode = 'kg';
        $orderline->taxPercent = 20;
        $orderlines[] = $orderline;

        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $api = $this->getRefundCaptureReservation();
        $api->setTransaction($transaction);
        $api->setOrderLines($orderlines);
        $api->call();

        $request = $api->getRawRequest();

        $this->assertEquals($this->getExceptedUri('refundCapturedReservation/'), $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $parts);

        $this->assertCount(2, $parts['orderLines']);
        $line = $parts['orderLines'][1];

        $this->assertEquals('Brown sugar', $line['description']);
        $this->assertEquals('productid2', $line['itemId']);
        $this->assertEquals('2.5', $line['quantity']);
        $this->assertEquals('8.75', $line['unitPrice']);
        $this->assertEquals('20', $line['taxPercent']);
        $this->assertEquals('kg', $line['unitCode']);

    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_transaction_orderlines_object()
    {
        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $api = $this->getRefundCaptureReservation();
        $api->setTransaction($transaction);
        $api->setOrderLines(new OrderLine('White sugar', 'productid', 1.5, 5.75));
        $api->call();

        $request = $api->getRawRequest();

        $this->assertEquals($this->getExceptedUri('refundCapturedReservation/'), $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $parts);

        $this->assertCount(1, $parts['orderLines']);

    }

    /**
     * @depends test_refund_reservation
     */
    public function test_capture_refund_transaction_orderlines_randomarray()
    {
        $this->setExpectedException(\InvalidArgumentException::class, sprintf(
            'orderLines should all be a instance of "%s"',
            OrderLine::class
        ));

        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $api = $this->getRefundCaptureReservation();
        $api->setTransaction($transaction);
        $api->setOrderLines(['myobject']);
        $api->call();
    }

    public function test_capture_refund_transaction_handleexception()
    {
        $this->setExpectedException(ClientException::class);

        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $client = $this->getClient($mock = new MockHandler([
            new Response(400, ['text-content' => 'application/xml'])
        ]));

        $api = (new RefundCapturedReservation())
            ->setClient($client)
            ->setAuthentication($this->getAuth())
            ->setTransactionId(123)
        ;
        $api->call();
    }

    public function test_capture_refund_transaction_require_auth()
    {
        $this->setExpectedException(AuthenticationRequiredException::class);

        $transaction = new Transaction();
        $transaction->TransactionId = 456;

        $client = $this->getClient($mock = new MockHandler([
            new Response(200, ['text-content' => 'application/xml'])
        ]));

        $api = (new RefundCapturedReservation())
            ->setClient($client)
            ->setTransactionId(123)
        ;
        $api->call();
    }

}
