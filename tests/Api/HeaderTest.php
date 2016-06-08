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

use Altapay\Api\Others\FundingList;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Response\Embeds\Header;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class HeaderTest extends AbstractApiTest
{

    /**
     * @return FundingList
     */
    protected function getapi()
    {
        $client = $this->getClient(new MockHandler([
            new Response(200, ['text-content' => 'application/xml'], file_get_contents(__DIR__ . '/Results/header_error.xml'))
        ]));

        return (new FundingList($this->getAuth()))
            ->setClient($client)
        ;
    }

    public function test_get_header_error()
    {
        $this->setExpectedException(ResponseHeaderException::class);
        $api = $this->getapi();
        $api->call();
    }

    public function test_get_header_error_data()
    {
        try {
            $api = $this->getapi();
            $api->call();
        } catch (ResponseHeaderException $e) {
            $this->assertInstanceOf(Header::class, $e->getHeader());
            $this->assertEquals('200', $e->getHeader()->ErrorCode);
            $this->assertEquals('This request has error', $e->getHeader()->ErrorMessage);
        }
    }

}
